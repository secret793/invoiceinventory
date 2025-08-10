use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConfirmedAffixLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('confirmed_affix_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->string('boe')->nullable();
            $table->string('sad_number')->nullable();
            $table->string('vehicle_number')->nullable();
            $table->string('regime')->nullable();
            $table->string('destination')->nullable();
            $table->unsignedBigInteger('destination_id')->nullable();
            $table->foreign('destination_id')->references('id')->on('destinations')->nullOnDelete();
            $table->unsignedBigInteger('route_id')->nullable();
            $table->foreign('route_id')->references('id')->on('routes')->nullOnDelete();
            $table->unsignedBigInteger('long_route_id')->nullable();
            $table->foreign('long_route_id')->references('id')->on('long_routes')->nullOnDelete();
            $table->date('manifest_date')->nullable();
            $table->string('agency')->nullable();
            $table->string('agent_contact')->nullable();
            $table->string('truck_number')->nullable();
            $table->string('driver_name')->nullable();
            $table->dateTime('affixing_date')->nullable();
            $table->string('status')->default('PENDING');
            $table->unsignedBigInteger('allocation_point_id')->nullable();
            $table->foreign('allocation_point_id')->references('id')->on('allocation_points')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('confirmed_affix_logs');
    }
}
