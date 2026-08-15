from fastapi import FastAPI
from pydantic import BaseModel
import pandas as pd
import joblib
from sklearn.model_selection import train_test_split
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
import numpy as np

app = FastAPI(
    title="Drug Price Recommendation API",
    version="1.0.0"
)

model = joblib.load("C:\\xampp\\htdocs\\ai-drug-price\\models\\medicine_price_model.pkl")

df = pd.read_csv("C:\\xampp\\htdocs\\ai-drug-price\\data\\cleaned_data_set\\cleaned.csv")


## This block for finding MAE, MSE, RMSE, r2
features = [
    "Generic Name",
    "Strength",
    "Pack Size",
    "Name of the Manufacturer",
    "Dosage Description",
    "Use For"
]

X = df[features]
y = df["Price"]

X_train, X_test, y_train, y_test = train_test_split(
    X,
    y,
    test_size=0.20,
    random_state=42
)

y_test_pred = model.predict(X_test)

mae = mean_absolute_error(y_test, y_test_pred)
mse = mean_squared_error(y_test, y_test_pred)
rmse = np.sqrt(mse)
r2 = r2_score(y_test, y_test_pred)
## End Block


## Input data structure

class MedicineInput(BaseModel):
    generic_name: str
    strength: str
    pack_size: str
    manufacturer: str
    dosage_description: str
    use_for: str


@app.get("/")
def home():
    return {
        "message": "landing page"
    }


##Prediction Block

@app.post("/predict")
def predict_price(medicine: MedicineInput):


    new_medicine = pd.DataFrame({
        "Generic Name": [medicine.generic_name],
        "Strength": [medicine.strength],
        "Pack Size": [medicine.pack_size],
        "Name of the Manufacturer":[medicine.manufacturer],        
        "Dosage Description": [medicine.dosage_description],
        "Use For": [medicine.use_for]
    })

## Prediction
    predicted_price = float(
        model.predict(new_medicine)[0]
    )

##Similar drugs
    similar = df[
        (df["Generic Name"].astype(str).str.strip().str.lower()
         == medicine.generic_name.strip().lower())
        &
        (df["Strength"].astype(str).str.strip().str.lower()
         == medicine.strength.strip().lower())
        &
        (df["Dosage Description"].astype(str).str.strip().str.lower()
         == medicine.dosage_description.strip().lower())
        &
        (df["Use For"].astype(str).str.strip().str.lower()
         == medicine.use_for.strip().lower())
    ].copy()


    if len(similar) > 0:
        market_min = float(
            similar["Price"].min()
        )
        market_max = float(
            similar["Price"].max()
        )
        max_row = similar.loc[similar["Price"].idxmax()]

        market_median = float(
            similar["Price"].median()
        )

    else:

        market_min = None
        market_max = None
        market_median = None
        max_row = None

##Competitive price recommendation
        
    recommended_price = predicted_price

    if market_min is not None and market_max is not None:

        recommended_price = min(
            max(predicted_price, market_min),
            market_max
        )

##return response
        
    return {

        "medicine": {
            "generic_name": medicine.generic_name,
            "strength": medicine.strength,
            "pack_size": medicine.pack_size,
            "manufacturer": medicine.manufacturer,
            "dosage_description": medicine.dosage_description,
            "use_for": medicine.use_for
        },

        "ai_prediction": round(
            predicted_price, 2
        ),

        "market_analysis": {
            "comparable_medicines": len(similar),
            "minimum_price": (
                round(market_min, 2)
                if market_min is not None
                else None
            ),

            "median_price": (
                round(market_median, 2)
                if market_median is not None
                else None
            ),

            "maximum_price": (
                round(market_max, 2)
                if market_max is not None
                else None
            )
        },

        "recommended_price": round(
            recommended_price, 2
        ),

        "model_performance": {
            "mae": round(mae, 2),
            "mse": round(mse, 2),
            "rmse": round(rmse, 2),
            "r2": round(r2, 4)
        },
    }
