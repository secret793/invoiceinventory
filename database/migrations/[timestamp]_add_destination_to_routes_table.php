use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDestinationToRoutesTable extends Migration
{
    public function up()
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->string('destination')->nullable(); // Add the destination column
        });
    }

    public function down()
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->dropColumn('destination');
        });
    }
} 