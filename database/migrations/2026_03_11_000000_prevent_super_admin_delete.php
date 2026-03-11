<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION prevent_super_admin_delete()
RETURNS trigger AS $$
BEGIN
    IF OLD.role = 'Super Admin' THEN
        RAISE EXCEPTION 'Cannot delete Super Admin user';
    END IF;

    RETURN OLD;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS prevent_super_admin_delete ON users;
CREATE TRIGGER prevent_super_admin_delete
BEFORE DELETE ON users
FOR EACH ROW
EXECUTE FUNCTION prevent_super_admin_delete();
SQL);
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
DROP TRIGGER IF EXISTS prevent_super_admin_delete ON users;
DROP FUNCTION IF EXISTS prevent_super_admin_delete();
SQL);
    }
};
