import difflib

def diff_files(f1, f2, label):
    with open(f1, "r", encoding="utf-8") as f:
        l1 = f.readlines()
    with open(f2, "r", encoding="utf-8") as f:
        l2 = f.readlines()
    diff = list(difflib.unified_diff(l1, l2, fromfile="bubut/"+label, tofile=label))
    print(f"\nDIFF FOR {label}:")
    print("".join(diff))

diff_files(
    r"c:\laragon\www\kpi-bubut\app\Models\Scopes\DepartmentScope.php",
    r"c:\laragon\www\kpi-netto\app\Models\Scopes\DepartmentScope.php",
    "netto/DepartmentScope.php"
)

diff_files(
    r"c:\laragon\www\kpi-bubut\app\Models\Scopes\DepartmentScope.php",
    r"c:\laragon\www\kpi-lilin\app\Models\Scopes\DepartmentScope.php",
    "lilin/DepartmentScope.php"
)
