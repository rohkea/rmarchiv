<?php

/*
 * rmarchiv.de
 * (c) 2016-2017 by Marcel 'ryg' Hering
 */

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Game;
use Illuminate\Http\Request;

class CDCController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $cdc = \DB::table('games_coupdecoeur')
            ->leftJoin('games', 'games.id', '=', 'games_coupdecoeur.game_id')
            ->leftJoin('games_developer', 'games.id', '=', 'games_developer.game_id')
            ->leftJoin('developer', 'games_developer.developer_id', '=', 'developer.id')
            ->leftJoin('makers', 'makers.id', '=', 'games.maker_id')
            ->leftJoin('comments', function ($join) {
                $join->on('comments.content_id', '=', 'games.id');
                $join->on('comments.content_type', '=', \DB::raw("'game'"));
            })
            ->leftJoin('games_files', 'games_files.game_id', '=', 'games.id')
            ->leftJoin('users', 'games_developer.user_id', '=', 'users.id')
            ->select([
                'games.id as gameid',
                'games.title as gametitle',
                'games.subtitle as gamesubtitle',
                'developer.name as developername',
                'developer.id as developerid',
                'developer.created_at as developerdate',
                'developer.user_id as developeruserid',
                'users.name as developerusername',
                'games.created_at as gamecreated_at',
                'makers.short as makershort',
                'makers.title as makertitle',
                'makers.id as makerid',
                'games_coupdecoeur.created_at as cdcdate',
            ])
            ->selectRaw('(SELECT COUNT(id) FROM comments WHERE content_id = games.id AND content_type = "game") as commentcount')
            ->selectRaw('(SELECT SUM(vote_up) FROM comments WHERE content_id = games.id AND content_type = "game") as voteup')
            ->selectRaw('(SELECT SUM(vote_down) FROM comments WHERE content_id = games.id AND content_type = "game") as votedown')
            ->selectRaw('MAX(games_files.release_type) as gametype')
            ->selectRaw("(SELECT STR_TO_DATE(CONCAT(release_year,'-',release_month,'-',release_day ), '%Y-%m-%d') FROM games_files WHERE game_id = games.id ORDER BY release_year DESC, release_month DESC, release_day DESC LIMIT 1) as releasedate")
            ->selectRaw('(SELECT COUNT(id) FROM games_coupdecoeur WHERE game_id = games.id) as cdccount')
            ->groupBy('games.id')
            ->orderBy('games_coupdecoeur.created_at', 'desc')
            ->get();

        $gametypes = \DB::table('games_files_types')
            ->select('id', 'title', 'short')
            ->get();
        $gtypes = [];
        foreach ($gametypes as $gt) {
            $t['title'] = $gt->title;
            $t['short'] = $gt->short;
            $gtypes[$gt->id] = $t;
        }

        return view('cdc.index', [
            'cdcs' => $cdc,
            'gametypes' => $gtypes,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('cdc.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'gamename' => 'required',
        ]);

        // Prüfen ob das Spiel auch wirklich existiert
        $title = explode(' -=- ', $request->get('gamename'));

        if (count($title) == 1) {
            $game = Game::whereTitle($title[0])
                ->first();
        } else {
            $game = Game::whereTitle($title[0])
                ->orWhere('subtitle', '=', $title[1])
                ->first();
        }

        \DB::table('games_coupdecoeur')->insert([
            'game_id' => $game->id,
            'user_id' => \Auth::id(),
            'created_at' => Carbon::now(),
        ]);

        return redirect()->action('MsgBoxController@cdc_add', [$game->id]);
    }
}
