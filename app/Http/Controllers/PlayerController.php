<?php

namespace App\Http\Controllers;

use App\Models\MySQL\Game;
use App\Models\MySQL\Player;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PlayerController extends Controller
{
    private $result;
    private $playerModel;
    private $gameModel;

    public function __construct(\stdClass $result,
                                Game $gameModel,
                                Player $playerModel)
    {
        $this->result = $result;
        $this->playerModel = $playerModel;
        $this->gameModel = $gameModel;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $gameId, $playerId)
    {
        $responseCode = Response::HTTP_OK;
        try {
            $playerInGame = $this->gameModel->getByGameIdAndPlayerId($gameId, $playerId);
            if (!$playerInGame) {
                throw new \Exception("Este jugador no pertenece a esta partida", Response::HTTP_NOT_FOUND);
            }

            $player = $this->playerModel->find($playerId);
            if (!$player) {
                throw new \Exception("Jugador no encontrado", Response::HTTP_NOT_FOUND);
            }

            $player->nick = $request->input('nick');
            $player->save();

            $this->result->message = "Player updated!";
            return response()->json($this->result, $responseCode);
        } catch (\Exception $e) {
            self::logRecord($e, $this->result);
            return response()->json($this->result, $this->result->code);
        }
    }

}
