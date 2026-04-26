import cv2
import os

input_folder = r"C:\For UTEM\year4sem1\FYP\project\dataset\realWorld"
output_folder = r"C:\For UTEM\year4sem1\FYP\project\dataset\realWorld"

os.makedirs(output_folder, exist_ok=True)

for filename in os.listdir(input_folder):
    img_path = os.path.join(input_folder, filename)
    img = cv2.imread(img_path)

    if img is not None:
        resized = cv2.resize(img, (300, 300), interpolation=cv2.INTER_AREA)
        cv2.imwrite(os.path.join(output_folder, filename), resized)

print("Done resizing!")