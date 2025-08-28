<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class ResetLog extends Model
{
    use HasFactory;
    protected $table = 'resets'; // Explicitly define if different from plural 'reset_logs'
    protected $fillable = ['user_id', 'subject_id', 'reset_by_admin_id', 'reset_time', 'reason'];
    public $timestamps = false; // We have reset_time
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
    public function admin()
    {
        return $this->belongsTo(User::class, 'reset_by_admin_id');
    }
}
