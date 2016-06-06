<?php

/**
 *    Copyright 2016 ppy Pty. Ltd.
 *
 *    This file is part of osu!web. osu!web is distributed with the hope of
 *    attracting more community contributions to the core ecosystem of osu!.
 *
 *    osu!web is free software: you can redistribute it and/or modify
 *    it under the terms of the Affero GNU General Public License version 3
 *    as published by the Free Software Foundation.
 *
 *    osu!web is distributed WITHOUT ANY WARRANTY; without even the implied
 *    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *    See the GNU Affero General Public License for more details.
 *
 *    You should have received a copy of the GNU Affero General Public License
 *    along with osu!web.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace App\Http\Controllers;

use Cache;
use Request;

class LivestreamsController extends Controller
{
    protected $section = 'community';

    public function index()
    {
        view()->share('current_action', 'getLive');

        $streams = Cache::remember('livestreams', 5, function () {
            $twitchApiUrl = config('osu.urls.twitch_livestreams_api');
            $data = json_decode(file_get_contents($twitchApiUrl));

            return $data->streams;
        });

        // dirty hack to add https urls to images
        // with allowance from nanaya
        foreach ($streams as &$stream) {
            foreach ($stream->preview as &$preview) {
                $preview = str_replace('http://', 'https://', $preview);
            }
        }

        $featuredStreamId = Cache::get('featuredStream');
        if ($featuredStreamId !== null) {
            $featuredStreamId = (string) $featuredStreamId;
            foreach ($streams as $stream) {
                if ((string) $stream->_id !== $featuredStreamId) {
                    continue;
                }
                $featuredStream = $stream;
                break;
            }
        }

        return view('community.live', compact('streams', 'featuredStream'));
    }

    public function promote()
    {
        priv_check('LivestreamPromote')->ensureCan();

        Cache::forever('featuredStream', (string) Request::input('id'));

        return js_view('layout.ujs-reload');
    }

    public function demote()
    {
        priv_check('LivestreamPromote')->ensureCan();

        Cache::forget('featuredStream');

        return js_view('layout.ujs-reload');
    }
}