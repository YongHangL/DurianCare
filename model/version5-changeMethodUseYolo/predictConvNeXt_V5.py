import os
import sys
import json
import torch
import torch.nn as nn

from PIL import Image
from torchvision import transforms
from torchvision.models import convnext_tiny


# =========================
# Settings - Version 5
# =========================
MODEL_PATH = r"C:\For UTEM\year4sem1\FYP\project\model\version5-changeMethodUseYolo\model_version5.pth"

# YOLO model used for leaf localization
# Change this path to your trained YOLO leaf detection model path
YOLO_MODEL_PATH = r"C:\For UTEM\year4sem1\FYP\project\model\version5-changeMethodUseYolo\yolo_runs\leaf_crop_model_v5\weights\best.pt"

DEFAULT_CLASS_NAMES = ["Algal", "Blight", "Healthy", "Phomopsis"]
IMG_SIZE = 224

# Set True if Version 5 uses YOLO leaf localization
USE_YOLO_LOCALIZATION = True


# =========================
# Image Path Settings
# =========================
# Set True when you want to test manually in PyCharm
# Set False when using website upload through PHP
USE_MANUAL_IMAGE = False

# Manual testing image path
MANUAL_IMAGE_PATH = r"C:\For UTEM\year4sem1\FYP\project\dataset\test\Phomopsis\T_Phom_102.jpg"


# =========================
# Get Image Path
# =========================
if USE_MANUAL_IMAGE:
    IMAGE_PATH = MANUAL_IMAGE_PATH
else:
    if len(sys.argv) < 2:
        print("ERROR|No image path received")
        sys.exit()

    IMAGE_PATH = sys.argv[1]


# =========================
# Check Paths
# =========================
if not os.path.exists(MODEL_PATH):
    print("ERROR|Version 5 model file not found")
    sys.exit()

if not os.path.exists(IMAGE_PATH):
    print("ERROR|Image file not found")
    sys.exit()

if USE_YOLO_LOCALIZATION and not os.path.exists(YOLO_MODEL_PATH):
    print("ERROR|YOLO leaf localization model file not found")
    sys.exit()


# =========================
# Device
# =========================
device = torch.device("cuda" if torch.cuda.is_available() else "cpu")


# =========================
# Load Checkpoint Safely
# =========================
try:
    checkpoint = torch.load(MODEL_PATH, map_location=device, weights_only=False)
except TypeError:
    checkpoint = torch.load(MODEL_PATH, map_location=device)


class_names = checkpoint.get("class_names", DEFAULT_CLASS_NAMES)
num_classes = len(class_names)
img_size = checkpoint.get("input_size", checkpoint.get("img_size", IMG_SIZE))


# =========================
# Build ConvNeXt-Tiny Model
# =========================
model = convnext_tiny(weights=None)

# ConvNeXt-Tiny classifier:
# classifier[0] = LayerNorm2d
# classifier[1] = Flatten
# classifier[2] = Linear
in_features = model.classifier[2].in_features
model.classifier[2] = nn.Linear(in_features, num_classes)

model.load_state_dict(checkpoint["model_state_dict"])
model = model.to(device)
model.eval()


# =========================
# Image Transform
# =========================
predict_transform = transforms.Compose([
    transforms.Resize((img_size, img_size)),
    transforms.ToTensor(),
    transforms.Normalize(
        mean=[0.485, 0.456, 0.406],
        std=[0.229, 0.224, 0.225]
    )
])


# =========================
# YOLO Leaf Localization Function
# =========================
def localize_leaf_with_yolo(image_path):
    """
    Detect leaf using YOLO and crop the most confident/largest leaf area.
    If no leaf is detected, return original image.
    """

    from ultralytics import YOLO

    yolo_model = YOLO(YOLO_MODEL_PATH)

    original_image = Image.open(image_path).convert("RGB")

    results = yolo_model(image_path, verbose=False)

    if len(results) == 0 or results[0].boxes is None or len(results[0].boxes) == 0:
        return original_image, False, None, "No leaf detected by YOLO. Original image was used."

    boxes = results[0].boxes

    best_box = None
    best_score = -1

    for box in boxes:
        xyxy = box.xyxy[0].cpu().numpy()
        conf = float(box.conf[0].cpu().numpy())

        x1, y1, x2, y2 = xyxy
        area = max(0, x2 - x1) * max(0, y2 - y1)

        # Combine confidence and area to choose the most useful leaf crop
        score = conf * area

        if score > best_score:
            best_score = score
            best_box = (int(x1), int(y1), int(x2), int(y2), round(conf * 100, 2))

    if best_box is None:
        return original_image, False, None, "No valid YOLO box found. Original image was used."

    x1, y1, x2, y2, yolo_confidence = best_box

    width, height = original_image.size

    # Add small padding around detected leaf
    padding = 20
    x1 = max(0, x1 - padding)
    y1 = max(0, y1 - padding)
    x2 = min(width, x2 + padding)
    y2 = min(height, y2 + padding)

    cropped_image = original_image.crop((x1, y1, x2, y2))

    crop_box = {
        "x1": x1,
        "y1": y1,
        "x2": x2,
        "y2": y2,
        "yolo_confidence": yolo_confidence
    }

    return cropped_image, True, crop_box, None


# =========================
# Predict
# =========================
try:
    localization_used = False
    crop_box = None
    warning = None

    if USE_YOLO_LOCALIZATION:
        image, localization_used, crop_box, warning = localize_leaf_with_yolo(IMAGE_PATH)
    else:
        image = Image.open(IMAGE_PATH).convert("RGB")

    image_tensor = predict_transform(image).unsqueeze(0).to(device)

    with torch.no_grad():
        outputs = model(image_tensor)
        probs = torch.softmax(outputs, dim=1)[0]

    probabilities = {}

    for cls, prob in zip(class_names, probs):
        probabilities[cls] = round(float(prob.item()) * 100, 2)

    top_index = int(torch.argmax(probs).item())
    final_prediction = class_names[top_index]
    final_confidence = probabilities[final_prediction]

    if final_confidence < 70:
        confidence_status = "Low confidence. The image may be unclear or the prediction may be wrong."
    else:
        confidence_status = "High confidence"

    payload = {
        "probabilities": probabilities,
        "final_prediction": final_prediction,
        "final_confidence": final_confidence,
        "confidence_status": confidence_status,

        # Extra Version 5 information
        "model_version": "Version 5 - YOLO Leaf Localization + ConvNeXt-Tiny",
        "localization_used": localization_used,
        "crop_box": crop_box,
        "warning": warning
    }

    print("SUCCESS|" + json.dumps(payload))

except Exception as e:
    print("ERROR|" + str(e))
    sys.exit()