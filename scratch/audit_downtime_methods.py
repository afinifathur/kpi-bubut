def find_downtime_methods(path, name):
    print(f"\nDOWNTIME METHODS IN {name.upper()}:")
    try:
        with open(path, "r", encoding="utf-8") as f:
            content = f.read()
        for method in ["downtimeEdit", "downtimeUpdate", "downtimeDestroy"]:
            if method in content:
                print(f"  Found {method}")
            else:
                print(f"  Missing {method}")
    except Exception as e:
        print("  Error:", str(e))

find_downtime_methods(r"c:\laragon\www\kpi-bubut\app\Http\Controllers\DailyReportController.php", "bubut")
find_downtime_methods(r"c:\laragon\www\kpi-netto\app\Http\Controllers\DailyReportController.php", "netto")
find_downtime_methods(r"c:\laragon\www\kpi-lilin\app\Http\Controllers\DailyReportController.php", "lilin")
