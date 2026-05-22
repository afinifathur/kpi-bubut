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
    print("".join(diff[:120])) # Print first 120 lines of diff

diff_files(
    r"c:\laragon\www\kpi-bubut\app\Http\Controllers\ExportController.php",
    r"c:\laragon\www\kpi-netto\app\Http\Controllers\ExportController.php",
    "netto/ExportController.php"
)

diff_files(
    r"c:\laragon\www\kpi-bubut\app\Http\Controllers\ExportController.php",
    r"c:\laragon\www\kpi-lilin\app\Http\Controllers\ExportController.php",
    "lilin/ExportController.php"
)
