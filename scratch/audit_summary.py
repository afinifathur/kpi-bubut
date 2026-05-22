import json
import os

with open(r"c:\laragon\www\kpi-bubut\scratch\audit_results.json", "r") as f:
    data = json.load(f)

def print_summary(target_name, target_data):
    print(f"\n=================== {target_name.upper()} ===================")
    
    # Let's group by folders: app/Http/Controllers, app/Models, etc.
    groups = {
        "Controllers": "app/Http/Controllers",
        "Models": "app/Models",
        "Middleware": "app/Http/Middleware",
        "Services": "app/Services",
        "Helpers": "app/Helpers",
        "Traits": "app/Traits",
        "Providers": "app/Providers",
        "Migrations": "database/migrations",
        "Seeders": "database/seeders",
        "Views": "resources/views",
        "JS/CSS": "resources/",
        "Routes": "routes",
        "Config": "config"
    }
    
    def get_group(file_path):
        for gname, prefix in groups.items():
            if file_path.startswith(prefix):
                return gname
        return "Other"
    
    print("\n[MISSING IN TARGET (Exists in Bubut, but NOT in target)]")
    grouped = {}
    for f in target_data["missing_in_target"]:
        g = get_group(f)
        grouped.setdefault(g, []).append(f)
    for g, files in sorted(grouped.items()):
        print(f"  {g}:")
        for f in files:
            print(f"    - {f}")
            
    print("\n[EXTRA IN TARGET (Exists in target, but NOT in Bubut)]")
    grouped = {}
    for f in target_data["extra_in_target"]:
        g = get_group(f)
        grouped.setdefault(g, []).append(f)
    for g, files in sorted(grouped.items()):
        print(f"  {g}:")
        for f in files:
            print(f"    - {f}")

    print("\n[DIFFERING FILES (Exists in both, but contents differ)]")
    grouped = {}
    for f in target_data["differing_files"]:
        g = get_group(f)
        grouped.setdefault(g, []).append(f)
    for g, files in sorted(grouped.items()):
        print(f"  {g}:")
        for f in files:
            print(f"    - {f}")

print_summary("netto", data["netto"])
print_summary("lilin", data["lilin"])
