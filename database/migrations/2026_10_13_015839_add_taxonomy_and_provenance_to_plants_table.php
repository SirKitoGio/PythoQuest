<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plants', function (Blueprint $table) {
            $table->string('family')->nullable()->after('scientific_name')->index();
            $table->text('common_names')->nullable()->after('family');
            $table->text('native_range')->nullable()->after('habitat');
            $table->string('distribution')->nullable()->after('native_range');
            $table->string('hardiness_zones')->nullable()->after('distribution');
            $table->string('care_requirements')->nullable()->after('hardiness_zones');
            $table->string('conservation_status', 2)->default('NE')->after('care_requirements');
            $table->unsignedTinyInteger('medicinal_rating')->nullable()->after('conservation_status');
            $table->boolean('weed_potential')->nullable()->after('medicinal_rating');
            $table->string('source_provider')->nullable()->after('source_label');
            $table->string('source_url')->nullable()->after('source_provider');
            $table->timestamp('source_retrieved_at')->nullable()->after('source_url');

            $table->string('habitat')->nullable()->change();
            $table->string('plant_parts')->nullable()->change();
            $table->text('physical_features')->nullable()->change();

            $table->unique('scientific_name');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
            DB::statement(<<<'SQL'
                ALTER TABLE plants ADD COLUMN search_vector tsvector GENERATED ALWAYS AS (
                    setweight(to_tsvector('simple', coalesce(scientific_name, '')), 'A') ||
                    setweight(to_tsvector('simple', coalesce(common_name, '') || ' ' || coalesce(common_names, '')), 'A') ||
                    setweight(to_tsvector('simple', coalesce(family, '')), 'B') ||
                    setweight(to_tsvector('simple', coalesce(distribution, '') || ' ' || coalesce(habitat, '')), 'C')
                ) STORED
                SQL);
            DB::statement('CREATE INDEX plants_search_vector_index ON plants USING GIN (search_vector)');
            DB::statement('CREATE INDEX plants_common_name_trgm_index ON plants USING GIN (common_name gin_trgm_ops)');
            DB::statement('CREATE INDEX plants_scientific_name_trgm_index ON plants USING GIN (scientific_name gin_trgm_ops)');
            DB::statement('CREATE INDEX plants_common_names_trgm_index ON plants USING GIN (common_names gin_trgm_ops)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS plants_common_name_trgm_index');
            DB::statement('DROP INDEX IF EXISTS plants_scientific_name_trgm_index');
            DB::statement('DROP INDEX IF EXISTS plants_common_names_trgm_index');
            DB::statement('ALTER TABLE plants DROP COLUMN IF EXISTS search_vector');
        }

        Schema::table('plants', function (Blueprint $table) {
            $table->dropUnique(['scientific_name']);
            $table->dropIndex(['family']);
            $table->dropColumn([
                'family', 'common_names', 'native_range', 'distribution', 'hardiness_zones',
                'care_requirements', 'conservation_status', 'medicinal_rating', 'weed_potential',
                'source_provider', 'source_url', 'source_retrieved_at',
            ]);
        });
    }
};
