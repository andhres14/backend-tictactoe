<?php

namespace App\Models\MySQL;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $table = "games";
    protected $fillable = [
        'first_player_id',
        'second_player_id',
        'box_1', 'box_2', 'box_3',
        'box_4', 'box_5', 'box_6',
        'box_7', 'box_8', 'box_9',
        'winner_id',
        'status',
        'current_turn'
    ];
    public $timestamps = false;

    public function firstPlayer() {
        return $this->belongsTo(Player::class, 'first_player_id');
    }

    public function secondPlayer() {
        return $this->belongsTo(Player::class, 'second_player_id');
    }

    public function winner() {
        return $this->belongsTo(Player::class, 'winner_id');
    }


    public function createOne(array $object)
    {
        $object = array_merge($object, ['created_at' => now()->format('Y-m-d H:i:s')]);
        return $this
            ->insertGetId($object);
    }

    public function updateOne(int $objectId, array $objectToUpdate)
    {
        $objectToUpdate = array_merge($objectToUpdate, ['updated_at' => now()->format('Y-m-d H:i:s')]);
        return $this
            ->where([
                [$this->table . '.id', $objectId]
            ])
            ->update($objectToUpdate);
    }

    public function getByGameIdAndPlayerId(int $gameId,
                                           int $playerId)
    {
        return $this
            ->where($this->table.'.id', $gameId)
            ->where(function ($query) use ($playerId) {
                $query->where('first_player_id', $playerId);
                $query->orWhere('second_player_id', $playerId);
            })
            ->first();
    }
}
