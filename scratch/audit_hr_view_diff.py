import difflib

def show_diff(f1, f2, label):
    with open(f1, "r", encoding="utf-8") as f:
        l1 = f.readlines()
    with open(f2, "r", encoding="utf-8") as f:
        l2 = f.readlines()
    diff = list(difflib.unified_diff(l1, l2, fromfile="bubut/"+label, tofile="netto/"+label))
    print(f"\nDIFF FOR {label} (showing first 50 lines of diff):")
    print("".join(diff[:50]))

show_diff(
    r"c:\laragon\www\kpi-bubut\resources\views\hr_report\create.blade.php",
    r"c:\laragon\www\kpi-netto\resources\views\hr_report\create.blade.php",
    "create.blade.php"
)
