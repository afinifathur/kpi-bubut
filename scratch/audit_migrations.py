import os

BUBUT_MIGS = sorted(os.listdir(r"c:\laragon\www\kpi-bubut\database\migrations"))
NETTO_MIGS = sorted(os.listdir(r"c:\laragon\www\kpi-netto\database\migrations"))
LILIN_MIGS = sorted(os.listdir(r"c:\laragon\www\kpi-lilin\database\migrations"))

print("BUBUT MIGRATIONS:")
for m in BUBUT_MIGS:
    print(" -", m)

print("\nNETTO MIGRATIONS:")
for m in NETTO_MIGS:
    print(" -", m)

print("\nLILIN MIGRATIONS:")
for m in LILIN_MIGS:
    print(" -", m)
