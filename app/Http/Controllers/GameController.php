<?php

namespace App\Http\Controllers;

use App\Models\MySQL\Game;
use App\Models\MySQL\Player;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GameController extends Controller
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
     * Create new Game
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $responseCode = Response::HTTP_CREATED;
        try {
            // create first player
            $playerCreated = $this->playerModel->create(['nick' => 'Jugador 1']);
            // create second player
            $secondPlayerCreated = $this->playerModel->create(['nick' => 'Jugador 2']);

            // create new game
            $gameCreated = $this->gameModel
                ->createOne([
                    'first_player_id' => $playerCreated->id,
                    'second_player_id' => $secondPlayerCreated->id,
                    'current_turn' => $playerCreated->id,
                    'status' => self::GAME_STATUS[0],
                ]);

            if (!$gameCreated) {
                throw new \Exception("Error al crear el juego", Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $this->result->gameId = $gameCreated;
            $this->result->gameBoxes = $this->buildGameBoard(false);
            $this->result->firstPlayer = ['id' => $playerCreated->id, 'nick' => $playerCreated->nick];
            $this->result->secondPlayer = ['id' => $secondPlayerCreated->id, 'nick' => $secondPlayerCreated->nick];
            $this->result->message = "Game created";

            return response()->json($this->result, $responseCode);
        } catch (\Exception $e) {
            self::logRecord($e, $this->result);
            return response()->json($this->result, $this->result->code);
        }
    }

    /**
     * Reset game and generate a new.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetGame(Request $request)
    {
        $responseCode = Response::HTTP_CREATED;
        try {
            $validator = validator($request->only(['first_player', 'second_player']), [
                'first_player' => 'required|numeric',
                'second_player' => 'required|numeric'
            ]);
            if ($validator->fails()) {
                throw new \Exception(self::getErrorsInMessage($validator->errors()->all()), Response::HTTP_BAD_REQUEST);
            }

            $playerCreated = $this->playerModel->find($request->second_player);
            $secondPlayerCreated = $this->playerModel->find($request->first_player);
            if (!$playerCreated || !$secondPlayerCreated) {
                throw new \Exception("Usuarios no encontrados para la partida actual!", Response::HTTP_NOT_FOUND);
            }
            // create new game
            $gameCreated = $this->gameModel
                ->createOne([
                    'first_player_id' => $playerCreated->id,
                    'second_player_id' => $secondPlayerCreated->id,
                    'current_turn' => $request->first_player,
                    'status' => self::GAME_STATUS[0]
                ]);

            if (!$gameCreated) {
                throw new \Exception("Error al crear el juego", Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $this->result->gameId = $gameCreated;
            $this->result->gameBoxes = $this->buildGameBoard(false);
            $this->result->message = "Game created";
            return response()->json($this->result, $responseCode);
        } catch (\Exception $e) {
            self::logRecord($e, $this->result);
            return response()->json($this->result, $this->result->code);
        }
    }

    /**
     * Get game info
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $responseCode = Response::HTTP_OK;
            $game = $this->gameModel->find($id);
            if (!$game || $game->status === self::GAME_STATUS[1]) {
                throw new \Exception("Juego no encontrado o finalizado previamente", Response::HTTP_NOT_FOUND);
            }

            $game->firstPlayer;
            $game->secondPlayer;

            $this->result->game = $game;
            $this->result->gameBoxes = $this->buildGameBoard(true, $game);
            return response()->json($this->result, $responseCode);
        } catch (\Exception $e) {
            self::logRecord($e, $this->result);
            return response()->json($this->result, $this->result->code);
        }
    }

    /**
     * Play method (users)
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $responseCode = Response::HTTP_OK;
        try {
            $validator = validator($request->only(['box_selected', 'player']), [
                'box_selected' => 'required|numeric',
                'player' => 'required|numeric'
            ]);
            if ($validator->fails()) {
                throw new \Exception(self::getErrorsInMessage($validator->errors()->all()), Response::HTTP_BAD_REQUEST);
            }

            $game = $this->gameModel->find($id);
            if (!$game || $game->status === self::GAME_STATUS[1]) { // finalizado
                throw new \Exception("Juego no encontrado o finalizado previamente", Response::HTTP_NOT_FOUND);
            }

            // validate if the user
            $playerInGame = $this->gameModel->getByGameIdAndPlayerId($id, $request->player);
            if (!$playerInGame) {
                throw new \Exception("Este jugador no pertenece a esta partida", Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            if (!in_array($request->box_selected, self::BOX_GAME)) {
                throw new \Exception("Casilla seleccionada no valida", Response::HTTP_BAD_REQUEST);
            }

            $boxSelected = "box_{$request->box_selected}";
            if (isset($game->{$boxSelected})) {
                throw new \Exception("Casilla seleccionada previamente", Response::HTTP_BAD_REQUEST);
            }

            $whoIs = ($game->first_player_id == $request->player) ? $request->player : $game->second_player_id;
            $game->{$boxSelected} = ($game->first_player_id == $request->player) ? self::MARKS['first'] : self::MARKS['second'];
            $game->current_turn = ($request->player == $playerInGame->id) ? $request->player : $game->second_player_id;

            $gamePending = $this->checkPendingBox($game);
            if (!$gamePending['completed']) {
                $game->save();
            }

            // check possible winner
            $checkIsaWin = $this->verifyPossibleWinner($game, $game->{$boxSelected});
            if ($checkIsaWin) { // winner
                $game->status = self::GAME_STATUS[1];
                $game->save();
                $this->result->gameOver = true;
                $this->result->winnerId = $whoIs;
                $this->result->boxWinners = $checkIsaWin;
            } else {
                // check if all boxes have data and there is no winner
                if ($gamePending['completed']) {
                    $game->save();
                    $this->result->message = "Empate! la partida ha terminado.";
                    $this->result->gameOver = true;
                    $this->result->winner = false;
                } else {
                    $this->result->message = "Selección realizada correctamente!";
                }
            }
            return response()->json($this->result, $responseCode);
        } catch (\Exception $e) {
            self::logRecord($e, $this->result);
            return response()->json($this->result, $this->result->code);
        }
    }


    /**
     * Verify pending box
     * @param $game
     * @return mixed
     */
    private function checkPendingBox($game)
    {
        $result['totalCompleted'] = 0;
        foreach (self::BOX_GAME as $numBox) {
            $box = "box_{$numBox}";
            if (isset($game->{$box})) {
                $result['totalCompleted'] += 1;
            }
        }
        $result['completed'] = (count(self::BOX_GAME) === $result['totalCompleted']);
        return $result;
    }

    /**
     * Verifiy possible winner
     * @param Game $game
     * @param string $playerMark
     * @return false|string[]
     */
    private function verifyPossibleWinner(Game $game, string $playerMark)
    {
        foreach (self::POSSIBILITIES as $pos) {
            $boxA = "box_{$pos[0]}";
            $boxB = "box_{$pos[1]}";
            $boxC = "box_{$pos[2]}";
            if ($game->{$boxA} == $playerMark && $game->{$boxB} == $playerMark && $game->{$boxC} == $playerMark) {
                return [$boxA => true, $boxB => true, $boxC => true];
            }
        }
        return false;
    }

    /**
     * Build skeleton board game
     * @param false $withData
     * @param $info
     * @return array
     */
    private function buildGameBoard($withData = false, $info = null) {
        // build body game
        $gameBoxes = [];
        foreach (self::BOX_GAME as $item) {
            $boxItem = "box_{$item}";
            $gameBoxes[$boxItem] = [
                'value' => ($withData) ? $info->{$boxItem} : null,
                'isWinner' => false
            ];
        }
        // =============
        return $gameBoxes;
    }
}
