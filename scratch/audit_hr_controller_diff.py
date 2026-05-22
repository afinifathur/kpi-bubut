import difflib

file1 = r"c:\laragon\www\kpi-bubut\app\Http\Controllers\HrReportController.php"
file2 = r"c:\laragon\www\kpi-netto\app\Http\Controllers\HrReportController.php"

with open(file1, "r", encoding="utf-8") as f:
    lines1 = f.readlines()

with open(file2, "r", encoding="utf-8") as f:
    lines2 = f.readlines()

diff = difflib.unified_diff(lines1, lines2, fromfile="bubut/HrReportController.php", tofile="netto/HrReportController.php")
print("".join(list(diff)[:100])) # Print first 100 lines of diff
