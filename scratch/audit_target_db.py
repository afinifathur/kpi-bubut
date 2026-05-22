import mysql.connector

def check_targets(db_name):
    print(f"\nTARGETS IN DATABASE {db_name.upper()}:")
    try:
        conn = mysql.connector.connect(
            host="127.0.0.1",
            user="root",
            password="",
            database=db_name
        )
        cursor = conn.cursor()
        
        # Check structure
        cursor.execute("DESCRIBE process_targets")
        print("  Columns:")
        for col in cursor.fetchall():
            print(f"    {col[0]}: {col[1]}")
            
        # Check distinct month/year
        cursor.execute("SELECT month, year, COUNT(*) FROM process_targets GROUP BY month, year")
        print("  Monthly record counts:")
        for row in cursor.fetchall():
            print(f"    Month: {row[0]}, Year: {row[1]} -> {row[2]} records")
            
        conn.close()
    except Exception as e:
        print("  Error:", str(e))

check_targets("kpi_netto")
check_targets("kpi_lilin")
