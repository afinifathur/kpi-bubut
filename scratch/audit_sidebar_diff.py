import difflib
import sys

# Reconfigure stdout to use utf-8
sys.stdout.reconfigure(encoding='utf-8')

def diff_files(f1, f2, label):
    with open(f1, "r", encoding="utf-8") as f:
        l1 = f.readlines()
    with open(f2, "r", encoding="utf-8") as f:
        l2 = f.readlines()
    diff = list(difflib.unified_diff(l1, l2, fromfile="bubut/"+label, tofile=label))
    print(f"\nDIFF FOR {label}:")
    print("".join(diff[:80])) # Print first 80 lines

diff_files(
    r"c:\laragon\www\kpi-bubut\resources\views\layouts\sidebar.blade.php",
    r"c:\laragon\www\kpi-netto\resources\views\layouts\sidebar.blade.php",
    "netto/sidebar.blade.php"
)

diff_files(
    r"c:\laragon\www\kpi-bubut\resources\views\layouts\sidebar.blade.php",
    r"c:\laragon\www\kpi-lilin\resources\views\layouts\sidebar.blade.php",
    "lilin/sidebar.blade.php"
)
