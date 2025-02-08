import sys
import json
import numpy as np
from sklearn.linear_model import LinearRegression
from datetime import datetime

# Read JSON input from Laravel
data = json.loads(sys.stdin.read())
expenses = data["expenses"]  # List of {"date": "YYYY-MM-DD", "amount": 10.5}

# Step 1: Aggregate expenses per day
daily_totals = {}
for entry in expenses:
    date = entry["date"]
    amount = entry["amount"]
    if date in daily_totals:
        daily_totals[date] += amount  # Sum expenses for the same day
    else:
        daily_totals[date] = amount

# Convert aggregated data into sorted daily array
sorted_dates = sorted(daily_totals.keys())  # Ensure chronological order
daily_expenses = [daily_totals[date] for date in sorted_dates]

# Step 2: Prepare data for prediction
x = np.array(range(1, len(daily_expenses) + 1)).reshape(-1, 1)  # Day numbers
y = np.array(daily_expenses).reshape(-1, 1)  # Expense amounts

# Train a linear regression model
model = LinearRegression()
model.fit(x, y)

# Get the total number of days in the current month
current_date = datetime.strptime(sorted_dates[0], "%Y-%m-%d")  # Get first date
days_in_month = (datetime(current_date.year, current_date.month % 12 + 1, 1) - datetime(current_date.year, current_date.month, 1)).days

# Step 3: Predict remaining days
days_provided = len(daily_expenses)
remaining_days = days_in_month - days_provided

predicted_expenses = [model.predict([[i]])[0][0] for i in range(days_provided + 1, days_in_month + 1)]
predicted_remaining = sum(predicted_expenses)

# Step 4: Calculate final total estimate
total_actual = sum(daily_expenses)
total_estimate = total_actual + predicted_remaining

# Output results
print(json.dumps({
    "daily_expenses": daily_expenses,
    "estimated_remaining": round(predicted_remaining, 2),
    "total_estimate": round(total_estimate, 2)
}))
