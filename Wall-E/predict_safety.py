import mysql.connector
import pandas as pd
import numpy as np
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import StandardScaler
from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import r2_score, mean_absolute_percentage_error
from datetime import datetime, timedelta
import joblib
import json
import sys
import time

def print_step(message):
    """Print a step message and flush immediately for real-time output"""
    print(message, flush=True)
    sys.stdout.flush()
    time.sleep(1)  # Add a small delay to make steps visible

# Database configuration
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'marsrover'
}

# Safety thresholds (from environment_status.php)
SAFETY_THRESHOLDS = {
    'humidity': {'min': 30, 'max': 90},
    'temperature': {'min': 18, 'max': 37},
    'mq2_lpg': {'max': 100},
    'mq2_methane': {'max': 100},
    'mq135_nh3': {'max': 50},
    'mq135_co2': {'max': 1000},
    'mq7_co': {'max': 50},
    'mq8_h2': {'max': 100}
}

def fetch_historical_data():
    """Fetch historical sensor data from the database"""
    conn = mysql.connector.connect(**DB_CONFIG)
    query = "SELECT * FROM sensor_data ORDER BY timestamp"
    df = pd.read_sql(query, conn)
    conn.close()
    return df

def prepare_data(df):
    """Prepare data for training"""
    # Convert timestamp to datetime if it isn't already
    df['timestamp'] = pd.to_datetime(df['timestamp'])
    
    # Add time-based features
    df['hour'] = df['timestamp'].dt.hour
    df['day_of_week'] = df['timestamp'].dt.dayofweek
    
    # Calculate rolling means for each sensor
    sensor_columns = ['humidity', 'temperature', 'mq2_lpg', 'mq2_methane', 
                     'mq135_nh3', 'mq135_co2', 'mq7_co', 'mq8_h2']
    
    for col in sensor_columns:
        df[f'{col}_rolling_mean'] = df[col].rolling(window=6).mean()
    
    # Drop rows with NaN values
    df = df.dropna()
    
    return df, sensor_columns

def train_prediction_models(df, sensor_columns):
    """Train prediction models for each sensor"""
    models = {}
    scalers = {}
    feature_names = {}
    accuracy_metrics = {}
    
    for sensor in sensor_columns:
        # Prepare features
        features = ['hour', 'day_of_week'] + [f'{col}_rolling_mean' for col in sensor_columns]
        X = df[features]
        y = df[sensor]
        
        # Split data
        X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)
        
        # Scale features
        scaler = StandardScaler()
        X_train_scaled = scaler.fit_transform(X_train)
        X_test_scaled = scaler.transform(X_test)
        
        # Train model
        model = RandomForestRegressor(n_estimators=100, random_state=42)
        model.fit(X_train_scaled, y_train)
        
        # Calculate accuracy metrics
        y_pred = model.predict(X_test_scaled)
        r2 = r2_score(y_test, y_pred)
        mape = mean_absolute_percentage_error(y_test, y_pred)
        accuracy = (1 - mape) * 100  # Convert to percentage accuracy
        
        # Store metrics
        accuracy_metrics[sensor] = {
            'accuracy': min(max(accuracy, 0), 100),  # Ensure between 0-100%
            'r2_score': r2
        }
        
        # Store model, scaler and feature names
        models[sensor] = model
        scalers[sensor] = scaler
        feature_names[sensor] = features
        
    return models, scalers, feature_names, accuracy_metrics

def predict_future_values(df, models, scalers, feature_names, hours_ahead=24):
    """Predict future sensor values"""
    predictions = []
    last_row = df.iloc[-1]
    current_time = pd.to_datetime(last_row['timestamp'])
    
    for hour in range(1, hours_ahead + 1):
        future_time = current_time + timedelta(hours=hour)
        
        # Initialize prediction row with rolling means
        pred_row = {}
        for sensor in models.keys():
            pred_row[f'{sensor}_rolling_mean'] = df[sensor].rolling(window=6).mean().iloc[-1]
        
        # Add time features
        pred_row['hour'] = future_time.hour
        pred_row['day_of_week'] = future_time.dayofweek
        
        # Make predictions for each sensor
        for sensor, model in models.items():
            # Create features DataFrame with correct column order
            features = pd.DataFrame([pred_row])[feature_names[sensor]]
            features_scaled = scalers[sensor].transform(features)
            
            # Get prediction and confidence interval
            pred_values = []
            for estimator in model.estimators_:
                pred_values.append(estimator.predict(features_scaled)[0])
            
            pred_value = np.mean(pred_values)
            pred_std = np.std(pred_values)
            
            pred_row[sensor] = pred_value
            pred_row[f'{sensor}_std'] = pred_std
        
        pred_row['timestamp'] = future_time
        predictions.append(pred_row)
    
    return pd.DataFrame(predictions)

def analyze_safety(predictions_df, accuracy_metrics):
    """Analyze predicted values for safety concerns"""
    safety_analysis = {
        'safe_duration': None,
        'warnings': [],
        'critical_points': [],
        'predictions': {},
        'accuracy_metrics': accuracy_metrics
    }
    
    # Prepare prediction data for visualization
    for column in predictions_df.columns:
        if column in SAFETY_THRESHOLDS:
            safety_analysis['predictions'][column] = predictions_df[column].tolist()
            # Add uncertainty ranges
            if f'{column}_std' in predictions_df.columns:
                safety_analysis['predictions'][f'{column}_uncertainty'] = predictions_df[f'{column}_std'].tolist()
    
    safety_analysis['timestamps'] = predictions_df['timestamp'].dt.strftime('%Y-%m-%d %H:%M:%S').tolist()
    
    # Calculate overall prediction confidence
    accuracies = [metrics['accuracy'] for metrics in accuracy_metrics.values()]
    safety_analysis['overall_accuracy'] = sum(accuracies) / len(accuracies)
    
    # Check each timepoint for safety violations
    for idx, row in predictions_df.iterrows():
        violations = []
        
        for param, limits in SAFETY_THRESHOLDS.items():
            value = row[param]
            std = row[f'{param}_std'] if f'{param}_std' in row else 0
            
            if 'min' in limits and value < limits['min']:
                violations.append(f"{param} too low ({value:.1f} ± {std:.1f})")
            elif 'max' in limits and value > limits['max']:
                violations.append(f"{param} too high ({value:.1f} ± {std:.1f})")
        
        if violations:
            timestamp = row['timestamp']
            if safety_analysis['safe_duration'] is None:
                safety_analysis['safe_duration'] = (timestamp - predictions_df['timestamp'].iloc[0]).total_seconds() / 3600
            
            safety_analysis['critical_points'].append({
                'timestamp': timestamp.strftime('%Y-%m-%d %H:%M:%S'),
                'violations': violations
            })
    
    if safety_analysis['safe_duration'] is None:
        safety_analysis['safe_duration'] = 24  # All predicted hours are safe
    
    return safety_analysis

def main():
    # Fetch and prepare data
    print_step("Step 1: Fetching historical data...")
    df = fetch_historical_data()
    df, sensor_columns = prepare_data(df)
    
    # Train models
    print_step("Step 2: Training prediction models...")
    models, scalers, feature_names, accuracy_metrics = train_prediction_models(df, sensor_columns)
    
    # Make predictions
    print_step("Step 3: Making predictions...")
    predictions_df = predict_future_values(df, models, scalers, feature_names)
    
    # Analyze safety
    print_step("Step 4: Analyzing safety conditions...")
    safety_analysis = analyze_safety(predictions_df, accuracy_metrics)
    
    # Save prediction data as JSON
    with open('prediction_data.json', 'w') as f:
        json.dump(safety_analysis, f)
    
    # Print results
    print("\nSafety Analysis Results:")
    print(f"Safe duration: {safety_analysis['safe_duration']:.1f} hours")
    print(f"\nPrediction Accuracy: {safety_analysis['overall_accuracy']:.1f}%")
    
    if safety_analysis['critical_points']:
        print("\nPotential hazards detected:")
        for point in safety_analysis['critical_points']:
            print(f"\nAt {point['timestamp']}:")
            for violation in point['violations']:
                print(f"- {violation}")
    else:
        print("\nNo immediate hazards detected in the next 24 hours.")
    
    # Save models for future use
    print("\nSaving models...")
    joblib.dump((models, scalers, feature_names), 'safety_prediction_models.joblib')

if __name__ == "__main__":
    main() 