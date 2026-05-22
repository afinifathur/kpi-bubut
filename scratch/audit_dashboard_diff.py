import difflib
import sys

sys.stdout.reconfigure(encoding='utf-8')

def diff_files(f1, f2, label):
    with open(f1, "r", encoding="utf-8") as f:
        l1 = f.readlines()
    with open(f2, "r", encoding="utf-8") as f:
        l2 = f.readlines()
    diff = list(difflib.unified_diff(l1, l2, fromfile="bubut/"+label, tofile=label))
    print(f"\nDIFF FOR {label}:")
    print("".join(diff[:80])) # Print first 80 lines of diff

diff_files(
    r"c:\laragon\www\kpi-bubut\resources\views\dashboard\index.blade.php",
    r"c:\laragon\www\kpi-netto\resources\views\dashboard\index.blade.php",
    "netto/dashboard_index.blade.php"
)

diff_files(
    r"c:\laragon\www\kpi-bubut\resources\views\dashboard\index.blade.php",
    r"c:\laragon\www\kpi-lilin\resources\views\dashboard\index.blade.php",
    "lilin/dashboard_index.blade.php"
)
