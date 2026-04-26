import os
import sys
import json

# Hide TensorFlow log messages
os.environ["TF_CPP_MIN_LOG_LEVEL"] = "3"

import numpy as np
import tensorflow as tf
from tensorflow.keras.utils import load_img, img_to_array

MODEL_PATH = r"C:\For UTEM\year4sem1\FYP\project\model\model_version2.keras"

class_names = ["Algal", "Blight", "Healthy", "Phomopsis"]

if len(sys.argv) < 2:
    print("ERROR|No image path received")
    sys.exit()
#IMAGE_PATH =r"C:\For UTEM\year4sem1\FYP\project\dataset\test\Phomopsis\T_Phom_102.jpg"
IMAGE_PATH = sys.argv[1]

if not os.path.exists(MODEL_PATH):
    print("ERROR|Model file not found")
    sys.exit()

if not os.path.exists(IMAGE_PATH):
    print("ERROR|Image file not found")
    sys.exit()

try:
    model = tf.keras.models.load_model(MODEL_PATH)

    img = load_img(IMAGE_PATH, target_size=(300, 300))
    img_array = img_to_array(img)
    img_array = np.expand_dims(img_array, axis=0)

    predictions = model.predict(img_array, verbose=0)
    preds = predictions[0]

    probabilities = {}
    for cls, prob in zip(class_names, preds):
        probabilities[cls] = round(float(prob) * 100, 2)

    top_index = int(np.argmax(preds))
    final_prediction = class_names[top_index]
    final_confidence = probabilities[final_prediction]

    payload = {
        "probabilities": probabilities,
        "final_prediction": final_prediction,
        "final_confidence": final_confidence
    }

    print("SUCCESS|" + json.dumps(payload))

except Exception as e:
    print("ERROR|" + str(e))
    sys.exit()