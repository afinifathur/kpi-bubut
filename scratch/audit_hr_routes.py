def find_hr_routes(path, name):
    print(f"\nHR ROUTES FOR {name.upper()}:")
    try:
        with open(path, "r", encoding="utf-8") as f:
            lines = f.readlines()
        found = False
        for i, line in enumerate(lines):
            if "hr_report" in line or "HrReport" in line or "hr-report" in line:
                # Print 5 lines around the hit
                start = max(0, i - 2)
                end = min(len(lines), i + 3)
                print(f"--- Line {i+1} ---")
                for j in range(start, end):
                    print(f"{j+1}: {lines[j].strip()}")
                found = True
        if not found:
            print("  No HR routes found.")
    except Exception as e:
        print("  Error:", str(e))

find_hr_routes(r"c:\laragon\www\kpi-bubut\routes\web.php", "bubut")
find_hr_routes(r"c:\laragon\www\kpi-netto\routes\web.php", "netto")
find_hr_routes(r"c:\laragon\www\kpi-lilin\routes\web.php", "lilin")
