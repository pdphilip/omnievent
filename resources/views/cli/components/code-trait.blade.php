<div class="m-1">
@include('omnievent::cli.components.status',[
    'name' => '1',
    'title' => 'Add the Eventable trait to your <span class="text-sky-500">'.$model.'</span> model',
    'status' => 'info',
])
<code line="7" start-line="1" class="m-2">
namespace App\Models;

use PDPhilip\OmniEvent\Eventable;

class {{$model}} extends Model
{
    use Eventable;
</code>
@include('omnievent::cli.components.hr',[
    'color' => 'text-sky-500',
])
</div>