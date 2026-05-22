def print_db_env(path, name):
    print(f"\nDB ENV FOR {name.upper()}:")
    try:
        with open(path, "r") as f:
            for line in f:
                if line.startswith("DB_") or line.startswith("MASTER_"):
                    print("  ", line.strip())
    except Exception as e:
        print("   Error:", str(e))

print_db_env(r"c:\laragon\www\kpi-bubut\.env", "bubut")
print_db_env(r"c:\laragon\www\kpi-netto\.env", "netto")
print_db_env(r"c:\laragon\www\kpi-lilin\.env", "lilin")
