import os
import hashlib
import json
import filecmp

# Define directories
BUBUT = r"c:\laragon\www\kpi-bubut"
NETTO = r"c:\laragon\www\kpi-netto"
LILIN = r"c:\laragon\www\kpi-lilin"

SUBDIRS = [
    "app/Http/Controllers",
    "app/Models",
    "app/Http/Middleware",
    "app/Helpers",
    "app/Traits",
    "app/Services",
    "app/Providers",
    "database/migrations",
    "database/seeders",
    "resources/views",
    "resources/js",
    "resources/css",
    "routes",
    "config"
]

def get_md5(file_path):
    if not os.path.exists(file_path):
        return None
    try:
        hash_md5 = hashlib.md5()
        with open(file_path, "rb") as f:
            for chunk in iter(lambda: f.read(4096), b""):
                hash_md5.update(chunk)
        return hash_md5.hexdigest()
    except Exception as e:
        return f"error: {str(e)}"

def get_all_files(base_dir, subdirs):
    files_list = []
    for subdir in subdirs:
        full_subdir = os.path.join(base_dir, os.path.normpath(subdir))
        if not os.path.exists(full_subdir):
            continue
        for root, _, files in os.walk(full_subdir):
            for file in files:
                full_path = os.path.join(root, file)
                rel_path = os.path.relpath(full_path, base_dir)
                files_list.append(rel_path.replace("\\", "/"))
    return files_list

def compare_codebases():
    # Get all files in Bubut
    bubut_files = set(get_all_files(BUBUT, SUBDIRS))
    netto_files = set(get_all_files(NETTO, SUBDIRS))
    lilin_files = set(get_all_files(LILIN, SUBDIRS))
    
    all_known_files = bubut_files.union(netto_files).union(lilin_files)
    
    report = {
        "netto": {
            "missing_in_target": [], # In Bubut, but not in Netto
            "extra_in_target": [],   # In Netto, but not in Bubut
            "differing_files": [],   # In both, but different
            "identical_files": []    # In both, and identical
        },
        "lilin": {
            "missing_in_target": [], # In Bubut, but not in Lilin
            "extra_in_target": [],   # In Lilin, but not in Bubut
            "differing_files": [],   # In both, but different
            "identical_files": []    # In both, and identical
        }
    }
    
    for rel_path in sorted(list(all_known_files)):
        bubut_path = os.path.join(BUBUT, os.path.normpath(rel_path))
        netto_path = os.path.join(NETTO, os.path.normpath(rel_path))
        lilin_path = os.path.join(LILIN, os.path.normpath(rel_path))
        
        bubut_exists = os.path.exists(bubut_path)
        netto_exists = os.path.exists(netto_path)
        lilin_exists = os.path.exists(lilin_path)
        
        bubut_md5 = get_md5(bubut_path) if bubut_exists else None
        netto_md5 = get_md5(netto_path) if netto_exists else None
        lilin_md5 = get_md5(lilin_path) if lilin_exists else None
        
        # Netto comparison
        if bubut_exists and not netto_exists:
            report["netto"]["missing_in_target"].append(rel_path)
        elif not bubut_exists and netto_exists:
            report["netto"]["extra_in_target"].append(rel_path)
        elif bubut_exists and netto_exists:
            if bubut_md5 == netto_md5:
                report["netto"]["identical_files"].append(rel_path)
            else:
                report["netto"]["differing_files"].append(rel_path)
                
        # Lilin comparison
        if bubut_exists and not lilin_exists:
            report["lilin"]["missing_in_target"].append(rel_path)
        elif not bubut_exists and lilin_exists:
            report["lilin"]["extra_in_target"].append(rel_path)
        elif bubut_exists and lilin_exists:
            if bubut_md5 == lilin_md5:
                report["lilin"]["identical_files"].append(rel_path)
            else:
                report["lilin"]["differing_files"].append(rel_path)
                
    return report

if __name__ == "__main__":
    rep = compare_codebases()
    
    # Save the output to a JSON file
    out_path = r"c:\laragon\www\kpi-bubut\scratch\audit_results.json"
    os.makedirs(os.path.dirname(out_path), exist_ok=True)
    with open(out_path, "w") as f:
        json.dump(rep, f, indent=2)
        
    print("Audit run complete!")
    print(f"Netto - Missing: {len(rep['netto']['missing_in_target'])}, Extra: {len(rep['netto']['extra_in_target'])}, Differing: {len(rep['netto']['differing_files'])}, Identical: {len(rep['netto']['identical_files'])}")
    print(f"Lilin - Missing: {len(rep['lilin']['missing_in_target'])}, Extra: {len(rep['lilin']['extra_in_target'])}, Differing: {len(rep['lilin']['differing_files'])}, Identical: {len(rep['lilin']['identical_files'])}")
