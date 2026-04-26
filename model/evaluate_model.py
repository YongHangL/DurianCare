import os
import numpy as np
import pandas as pd
import tensorflow as tf
import matplotlib.pyplot as plt

from sklearn.metrics import (
    confusion_matrix,
    classification_report,
    accuracy_score,
    precision_recall_fscore_support
)

# =========================
# Settings
# =========================
MODEL_VERSION = "Model Version 2"

MODEL_PATH = r"C:\For UTEM\year4sem1\FYP\project\model\model_version2.keras"

# Change to test folder if needed
EVAL_DIR = r"C:\For UTEM\year4sem1\FYP\project\dataset\test"

IMG_SIZE = (300, 300)
BATCH_SIZE = 16

OUTPUT_DIR = r"C:\For UTEM\year4sem1\FYP\project\model\evaluation_images"

# =========================
# Check paths
# =========================
if not os.path.exists(MODEL_PATH):
    raise FileNotFoundError(f"Model file not found: {MODEL_PATH}")

if not os.path.exists(EVAL_DIR):
    raise FileNotFoundError(f"Evaluation folder not found: {EVAL_DIR}")

os.makedirs(OUTPUT_DIR, exist_ok=True)

# =========================
# Load dataset
# =========================
eval_ds = tf.keras.utils.image_dataset_from_directory(
    EVAL_DIR,
    label_mode="categorical",
    image_size=IMG_SIZE,
    batch_size=BATCH_SIZE,
    shuffle=False
)

class_names = eval_ds.class_names
print("Class names:", class_names)

AUTOTUNE = tf.data.AUTOTUNE
eval_ds = eval_ds.prefetch(buffer_size=AUTOTUNE)

# =========================
# Load model
# =========================
print("\nLoading model...")
model = tf.keras.models.load_model(MODEL_PATH)
print("Model loaded successfully.")

# =========================
# Get true labels
# =========================
y_true = []

for images, labels in eval_ds:
    y_true.extend(np.argmax(labels.numpy(), axis=1))

y_true = np.array(y_true)

# =========================
# Predict
# =========================
print("\nPredicting images...")
y_pred_prob = model.predict(eval_ds, verbose=1)
y_pred = np.argmax(y_pred_prob, axis=1)

# =========================
# Metrics
# =========================
accuracy = accuracy_score(y_true, y_pred)
cm = confusion_matrix(y_true, y_pred)

support = cm.sum(axis=1)
total_images = support.sum()

report_dict = classification_report(
    y_true,
    y_pred,
    target_names=class_names,
    output_dict=True,
    zero_division=0
)

precision_macro, recall_macro, f1_macro, _ = precision_recall_fscore_support(
    y_true,
    y_pred,
    average="macro",
    zero_division=0
)

precision_weighted, recall_weighted, f1_weighted, _ = precision_recall_fscore_support(
    y_true,
    y_pred,
    average="weighted",
    zero_division=0
)

print("\nAccuracy:", round(accuracy * 100, 2), "%")
print("Total images:", total_images)
print("Support per class:", dict(zip(class_names, support)))

# =========================
# Helper: y-axis labels with support
# =========================
class_labels_with_volume = [
    f"{name}\n(n={count})"
    for name, count in zip(class_names, support)
]

# =========================
# Plot Confusion Matrix Image
# =========================
def plot_confusion_matrix_image(cm, class_names, y_labels, save_path):
    fig, ax = plt.subplots(figsize=(10, 8))

    im = ax.imshow(cm, interpolation="nearest")

    ax.set_title(
        f"Confusion Matrix - {MODEL_VERSION}\n"
        f"Total Images: {total_images} | Accuracy: {accuracy * 100:.2f}%",
        fontsize=16,
        fontweight="bold",
        pad=18
    )

    cbar = plt.colorbar(im, ax=ax)
    cbar.set_label("Volume / Number of Images", rotation=270, labelpad=20)

    tick_marks = np.arange(len(class_names))

    ax.set_xticks(tick_marks)
    ax.set_yticks(tick_marks)

    ax.set_xticklabels(class_names, rotation=45, ha="right", fontsize=12)
    ax.set_yticklabels(y_labels, fontsize=12)

    ax.set_ylabel("Actual Class with Volume", fontsize=13)
    ax.set_xlabel("Predicted Class", fontsize=13)

    threshold = cm.max() / 2

    for i in range(cm.shape[0]):
        for j in range(cm.shape[1]):
            value = cm[i, j]
            ax.text(
                j,
                i,
                str(value),
                ha="center",
                va="center",
                fontsize=13,
                color="white" if value > threshold else "black",
                fontweight="bold" if i == j else "normal"
            )

    ax.text(
        0.5,
        -0.22,
        "Note: Diagonal values show correct predictions. Off-diagonal values show misclassifications.",
        transform=ax.transAxes,
        ha="center",
        fontsize=11
    )

    plt.tight_layout()
    plt.savefig(save_path, dpi=300, bbox_inches="tight")
    plt.close()


cm_image_path = os.path.join(OUTPUT_DIR, "confusion_matrix_model_version2.png")

plot_confusion_matrix_image(
    cm,
    class_names,
    class_labels_with_volume,
    cm_image_path
)

print("Saved:", cm_image_path)

# =========================
# Plot Normalized Confusion Matrix Image
# =========================
cm_normalized = cm.astype("float") / cm.sum(axis=1)[:, np.newaxis]
cm_normalized = np.nan_to_num(cm_normalized)
cm_percent = cm_normalized * 100

def plot_normalized_confusion_matrix_image(cm_percent, class_names, y_labels, save_path):
    fig, ax = plt.subplots(figsize=(10, 8))

    im = ax.imshow(cm_percent, interpolation="nearest", vmin=0, vmax=100)

    ax.set_title(
        f"Normalized Confusion Matrix (%) - {MODEL_VERSION}\n"
        f"Total Images: {total_images} | Accuracy: {accuracy * 100:.2f}%",
        fontsize=16,
        fontweight="bold",
        pad=18
    )

    cbar = plt.colorbar(im, ax=ax)
    cbar.set_label("Percentage (%)", rotation=270, labelpad=20)

    tick_marks = np.arange(len(class_names))

    ax.set_xticks(tick_marks)
    ax.set_yticks(tick_marks)

    ax.set_xticklabels(class_names, rotation=45, ha="right", fontsize=12)
    ax.set_yticklabels(y_labels, fontsize=12)

    ax.set_ylabel("Actual Class with Volume", fontsize=13)
    ax.set_xlabel("Predicted Class", fontsize=13)

    threshold = cm_percent.max() / 2

    for i in range(cm_percent.shape[0]):
        for j in range(cm_percent.shape[1]):
            value = cm_percent[i, j]
            ax.text(
                j,
                i,
                f"{value:.1f}%",
                ha="center",
                va="center",
                fontsize=13,
                color="white" if value > threshold else "black",
                fontweight="bold" if i == j else "normal"
            )

    ax.text(
        0.5,
        -0.22,
        "Note: Each row adds up to 100% and shows how each actual class was predicted.",
        transform=ax.transAxes,
        ha="center",
        fontsize=11
    )

    plt.tight_layout()
    plt.savefig(save_path, dpi=300, bbox_inches="tight")
    plt.close()


cm_norm_image_path = os.path.join(
    OUTPUT_DIR,
    "normalized_confusion_matrix_model_version2.png"
)

plot_normalized_confusion_matrix_image(
    cm_percent,
    class_names,
    class_labels_with_volume,
    cm_norm_image_path
)

print("Saved:", cm_norm_image_path)

# =========================
# Classification Report as Image
# =========================
def plot_classification_report_image(report_dict, save_path):
    rows = []

    for cls in class_names:
        rows.append([
            cls,
            f"{report_dict[cls]['precision']:.2f}",
            f"{report_dict[cls]['recall']:.2f}",
            f"{report_dict[cls]['f1-score']:.2f}",
            f"{int(report_dict[cls]['support'])}"
        ])

    rows.append([
        "Accuracy",
        "-",
        "-",
        f"{accuracy:.2f}",
        f"{total_images}"
    ])

    rows.append([
        "Macro Avg",
        f"{report_dict['macro avg']['precision']:.2f}",
        f"{report_dict['macro avg']['recall']:.2f}",
        f"{report_dict['macro avg']['f1-score']:.2f}",
        f"{int(report_dict['macro avg']['support'])}"
    ])

    rows.append([
        "Weighted Avg",
        f"{report_dict['weighted avg']['precision']:.2f}",
        f"{report_dict['weighted avg']['recall']:.2f}",
        f"{report_dict['weighted avg']['f1-score']:.2f}",
        f"{int(report_dict['weighted avg']['support'])}"
    ])

    columns = ["Class", "Precision", "Recall", "F1-score", "Support"]

    fig, ax = plt.subplots(figsize=(10, 5))
    ax.axis("off")

    ax.set_title(
        f"Classification Report - {MODEL_VERSION}\n"
        f"Total Images: {total_images} | Accuracy: {accuracy * 100:.2f}%",
        fontsize=16,
        fontweight="bold",
        pad=20
    )

    table = ax.table(
        cellText=rows,
        colLabels=columns,
        cellLoc="center",
        loc="center"
    )

    table.auto_set_font_size(False)
    table.set_fontsize(11)
    table.scale(1, 1.8)

    for key, cell in table.get_celld().items():
        row, col = key

        if row == 0:
            cell.set_text_props(weight="bold")
            cell.set_height(0.12)

        if col == 0:
            cell.set_text_props(weight="bold")

    plt.tight_layout()
    plt.savefig(save_path, dpi=300, bbox_inches="tight")
    plt.close()


report_image_path = os.path.join(
    OUTPUT_DIR,
    "classification_report_model_version2.png"
)

plot_classification_report_image(report_dict, report_image_path)

print("Saved:", report_image_path)

# =========================
# Summary Metrics as Image
# =========================
def plot_summary_metrics_image(save_path):
    summary_rows = [
        ["Accuracy", f"{accuracy:.4f}", f"{accuracy * 100:.2f}%"],
        ["Macro Precision", f"{precision_macro:.4f}", f"{precision_macro * 100:.2f}%"],
        ["Macro Recall", f"{recall_macro:.4f}", f"{recall_macro * 100:.2f}%"],
        ["Macro F1-score", f"{f1_macro:.4f}", f"{f1_macro * 100:.2f}%"],
        ["Weighted Precision", f"{precision_weighted:.4f}", f"{precision_weighted * 100:.2f}%"],
        ["Weighted Recall", f"{recall_weighted:.4f}", f"{recall_weighted * 100:.2f}%"],
        ["Weighted F1-score", f"{f1_weighted:.4f}", f"{f1_weighted * 100:.2f}%"],
        ["Total Images", str(total_images), "-"]
    ]

    columns = ["Metric", "Value", "Percentage"]

    fig, ax = plt.subplots(figsize=(9, 5))
    ax.axis("off")

    ax.set_title(
        f"Summary Metrics - {MODEL_VERSION}",
        fontsize=16,
        fontweight="bold",
        pad=20
    )

    table = ax.table(
        cellText=summary_rows,
        colLabels=columns,
        cellLoc="center",
        loc="center"
    )

    table.auto_set_font_size(False)
    table.set_fontsize(11)
    table.scale(1, 1.8)

    for key, cell in table.get_celld().items():
        row, col = key

        if row == 0:
            cell.set_text_props(weight="bold")
            cell.set_height(0.12)

        if col == 0:
            cell.set_text_props(weight="bold")

    plt.tight_layout()
    plt.savefig(save_path, dpi=300, bbox_inches="tight")
    plt.close()


summary_image_path = os.path.join(
    OUTPUT_DIR,
    "summary_metrics_model_version2.png"
)

plot_summary_metrics_image(summary_image_path)

print("Saved:", summary_image_path)

print("\nEvaluation image generation completed.")
print("All image results saved in:")
print(OUTPUT_DIR)