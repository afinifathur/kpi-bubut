import json

with open(r"c:\laragon\www\kpi-bubut\scratch\audit_results.json", "r") as f:
    data = json.load(f)

print("NETTO MISSING FILES:")
for x in data["netto"]["missing_in_target"]:
    print(" -", x)

print("\nNETTO EXTRA FILES:")
for x in data["netto"]["extra_in_target"]:
    print(" -", x)

print("\nNETTO DIFFERING FILES:")
for x in data["netto"]["differing_files"]:
    print(" -", x)
