import os

# 🔧 CHANGE THIS PATH
folder_path = r"C:\For UTEM\year4sem1\FYP\project\dataset\validate\Phomopsis"

# 🔧 CHANGE PREFIX
prefix = "Val_Phomopsis"

# 🔧 START NUMBER
start_number = 1

# =========================
# STEP 1: GET FILE LIST
# =========================
files = sorted(os.listdir(folder_path))

# keep only files (ignore folders)
files = [f for f in files if os.path.isfile(os.path.join(folder_path, f))]

# =========================
# STEP 2: RENAME TO TEMP (avoid conflict)
# =========================
for i, filename in enumerate(files):
    old_path = os.path.join(folder_path, filename)
    temp_path = os.path.join(folder_path, f"temp_{i}.jpg")
    os.rename(old_path, temp_path)

# =========================
# STEP 3: FINAL RENAME
# =========================
temp_files = sorted(os.listdir(folder_path))

count = start_number

for filename in temp_files:
    old_path = os.path.join(folder_path, filename)

    if not filename.startswith("temp_"):
        continue

    ext = os.path.splitext(filename)[1]

    new_name = f"{prefix}_{count:03d}{ext}"
    new_path = os.path.join(folder_path, new_name)

    os.rename(old_path, new_path)

    count += 1

print("✅ Renaming completed safely!")