<?php

$DEBUG = true;
$NEED_TRANSLATION = true;


/**
 * Implements the functions required by Audio Station/DSAudio.
 *
 * @author Ludy Su (https://github.com/LudySu/Synology-LrcPlugin)
 * @see https://global.download.synology.com/download/Document/DeveloperGuide/AS_Guide.pdf
 */
class LudysuNetEaseLrc {
    private $mArtist = "";
    private $mTitle = "";

    /**
     * Searches for a lyric with the artist and title, and returns the result list.
     */
    public function getLyricsList($artist, $title, $info) {
        $artist = trim($artist);
        $title = trim($title);
        $this->mArtist = $artist;
        $this->mTitle = $title;
        if ($this->isNullOrEmptyString($title)) {
            return 0;
        }

        $response = $this->search($title);
        if ($this->isNullOrEmptyString($response)) {
            return 0;
        }

        $json = json_decode($response, true);
        $songArray = $json['result']['songs'];

        if(count($songArray) == 0) {
            return 0;
        }

        // Try to find the titles that match exactly
        $exactMatchArray = array();
        $partialMatchArray = array();
        foreach ($songArray as $song) {
            $lowTitle = strtolower($title);
            $lowResult = strtolower($song['name']);
            if (strtolower($lowTitle) === strtolower($lowResult)) {
                array_push($exactMatchArray, $song);
            } else if (strpos($lowResult, $lowTitle) !== FALSE || strpos($lowTitle, $lowResult) !== FALSE) {
                array_push($partialMatchArray, $song);
            }
        }

        if (count($exactMatchArray) != 0) {
            $songArray = $exactMatchArray;
        } else if (count($partialMatchArray != 0)) {
            $songArray = $partialMatchArray;
        }

        // Get information from songs
        $foundArray = array();
        foreach ($songArray as $song) {
            $elem = array(
                'id' => $song['id'],
                'artist' => $song['artists'][0]["name"],
                'title' => $song['name'],
                'alt' => $song['alias'][0] . "; Album: " . $song['album']['name']
            );

            // Find the best match artist from all artists belong to a song
            $max = 0;
            foreach ($song['artists'] as $item) {
                $score = $this->getStringSimilarity($artist, $item['name']);
                if ($score > $max) {
                    $max = $distance;
                    $elem['artist'] = $item['name'];
                }
            }

            array_push($foundArray, $elem);
        }

        // Sort by best match according to similarity of artist and title
        usort($foundArray, array($this,'cmp'));
        foreach ($foundArray as $song) {
            // add artist, title, id, lrc preview (or additional comment)
            $info->addTrackInfoToList($song['artist'], $song['title'], $song['id'], $song['id'] . "; " . $song['alt']);
        }

        return count($foundArray);
    }

    /**
     * Downloads a file with the specific ID
     */
    public function getLyrics($id, $info) {
        $lrc = $this->downloadLyric($id);
        if ($this->isNullOrEmptyString($lrc)) {
            printf("download lyrics from server failed\n");
            return FALSE;
        }

        $info->addLyrics($lrc, $id);

        return true;
    }

    private function cmp($lhs, $rhs) {
        $scoreArtistL = $this->getStringSimilarity($this->mArtist, $lhs['artist']);
        $scoreArtistR = $this->getStringSimilarity($this->mArtist, $rhs['artist']);
        $scoreTitleL = $this->getStringSimilarity($this->mTitle, $lhs['title']);
        $scoreTitleR = $this->getStringSimilarity($this->mTitle, $rhs['title']);

        printf("artist " . $lhs['artist'] . " vs " . $rhs['artist'] . " | " . $scoreArtistL . " vs " . $scoreArtistR . "</br>");
        printf("title " . $lhs['title'] . " vs " . $rhs['title'] . " | " . $scoreTitleL . " vs " . $scoreTitleR. "</br>");

        return $scoreArtistR + $scoreTitleR - $scoreArtistL - $scoreTitleL;
    }

    /**
     * Gets similarity score of 0-100 between 2 strings, the bigger the score is, the more similarity.
     */
    private static function getStringSimilarity($lhs, $rhs) {
        similar_text($lhs, $rhs, $percent);
        return $percent;
    }

    private static function search($word) {
        $params = array(
            's' => $word,
            'offset' => '0', 'limit' => '20',
            'total' => true,
            'type' => '1', //搜索单曲(1)，歌手(100)，专辑(10)，歌单(1000)，用户(1002)
        );

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://music.163.com/api/search/pc",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
        ));

        $output = curl_exec($curl);
        curl_close($curl);

        return $output;
    }

    /**
     * Gets all lyrics, apart from original one, translated and karaoke versions will also be returned if available.
     */
    private function downloadLyric($music_id) {
        // lv = original version; tv = translated version; kv = karaoke version, rarely available. Set value to 0 if don't want
        $url = "http://music.163.com/api/song/lyric?os=pc&id=" . $music_id . "&lv=-1&kv=0&tv=-1";
        $response = '{"sgc":false,"sfy":false,"qfy":false,"transUser":{"id":479938456,"status":99,"demand":1,"userid":124108722,"nickname":"丶吟游","uptime":1495604317130},"lrc":{"version":2,"lyric":"[by:丶吟游]\n[00:00.00] 作曲 : 田淵智也\n[00:00.27] 作词 : LiSA\n[00:00.81]\n[00:21.81]そっと 吐き出す ため息を吸い込んだ 後悔は苦い味残して\n[00:31.37]いつも なんで？ 肝心なこと言えないまま 次の朝日が顔だしてる\n[00:39.81]\n[00:40.14]嫌になった運命を ナイフで切り刻んで\n[00:46.26]もう一度やり直したら キミに出会えないかも\n[00:55.36]\n[00:55.89]僕の声が響いた瞬間に始まる 命のリミット 心臓がカウントしてる\n[01:07.22]叶えても叶えても 終わらない願い\n[01:16.05]汗をかいて走った 世界の秒針は いつか止まった僕を置いていく\n[01:27.48]あと何回キミと笑えるの？\n[01:33.82]試してるんだ 僕を Catch the Moment\n[01:37.82]\n[01:47.62]一個幸せを数えるたびに 変わっていく未来に怯えてしまうけど\n[01:57.05]\n[01:57.38]愛情の種を大切に育てよう\n[02:04.20]分厚い雲も やがて突き破るかな\n[02:11.43]\n[02:11.69]キミの声が響いた 僕の全身を通って 心臓のドアをノックしてる\n[02:23.03]「臆病」でも開けちゃうんだよ 信じたいから\n[02:31.74]何にもないと思ったはずの足元に いつか深く確かな根を生やす\n[02:43.24]嵐の夜が来たとしても 揺らいだりはしない\n[02:52.05]\n[02:52.29]何度でも\n[02:52.91]追いついたり 追い越したり キミがふいに分かんなくなって\n[02:57.98]息をしたタイミングが合うだけで 嬉しくなったりして\n[03:04.83]集めた一秒を 永遠にして行けるかな\n[03:16.45]\n[03:27.67]僕の声が響いた瞬間に始まる 命のリミット 心臓がカウントしてる\n[03:38.78]叶えても叶えても 終わらない願い\n[03:47.70]汗をかいて走った 世界の秒針が いつか止まった僕を置いていく\n[03:59.05]あと何回キミと笑えるの？\n[04:05.35]試してるんだ 僕を Catch the Moment\n[04:09.43]\n[04:10.60]逃さないよ僕は\n[04:12.86]この瞬間を掴め Catch the Moment\n[04:20.14]\n"},"klyric":{"version":0,"lyric":""},"tlyric":{"version":2,"lyric":"[00:21.81]轻轻吐出的叹息 又默默咽回腹中 反悔总是留下苦涩的余味\n[00:31.37]这到底是为什么？重要的话还未来得及说出口 翌日的朝阳却已露出了脸庞\n[00:40.14]深恶痛疾的命运 用利刃切成粉碎\n[00:46.26]即使一切能从头来过 或许你我也无法邂逅\n[00:55.89]在我的声音响起的瞬间 心脏也随即开始倒数着生命的极限\n[01:07.22]无论多少次得偿所愿 愿望却始终不见尽头\n[01:16.05]挥洒汗水竭力奔走 世界的秒针 终有一日会抛下裹足不前的我\n[01:27.48]余生我又能与你共笑多少次？\n[01:33.82]这正是上帝赋予我的严峻考验 把握这一瞬\n[01:47.62]细数每一个降临的幸福 就会对渐渐改变的未来感到恐惧\n[01:57.38]用心的培育爱情的种子\n[02:04.20]天那边厚重的乌云 不久也会云开雾散\n[02:11.69]你的声音响起 贯穿我的全身 叩响心脏之门\n[02:23.03]就算胆怯 也要把心扉打开 因为我想要坚信\n[02:31.74]曾以为 空无一物的脚边 不知何时 长出深根扎于大地\n[02:43.24]就算风雨交加的夜晚来袭 我也不会有任何动摇\n[02:52.29]无论多少次\n[02:52.91]我都会追寻着你 追赶上你 就算蓦然之间 变得不再懂你\n[02:57.98]只要有那么一瞬间与你情投意合 我就无比欣喜\n[03:04.83]所收集的每一秒 能否拼凑成永恒\n[03:27.67]在我的声音响起的瞬间 心脏也随即开始倒数着生命的极限\n[03:38.78]无论多少次得偿所愿 愿望却始终不见尽头\n[03:47.70]挥洒汗水竭力奔走 世界的秒针 终有一日会抛下裹足不前的我\n[03:59.05]余生我又能与你共笑多少次？\n[04:05.35]这正是上帝赋予我的严峻考验 把握这一瞬\n[04:10.60]我不会再错过\n[04:12.86]紧握这个瞬间"},"code":200}';//$this->download($url);
        if ($this->isNullOrEmptyString($response)) {
            return NULL;
        }

        $json = json_decode($response, true);
        $orgLrc = $json['lrc']['lyric'];
        $transLrc = $json['tlyric']['lyric']; // Chinese translation lyric, but only some songs have

        global $NEED_TRANSLATION;
        $resultLrc = $orgLrc;
        if ($NEED_TRANSLATION && !$this->isNullOrEmptyString($transLrc)) {
            $resultLrc = "";
            $orgLines = $this->processLrcLine($orgLrc);
            $transLines = $this->processLrcLine($transLrc);
            var_dump($orgLines);

            foreach ($orgLines as $key => $value) {
                $resultLrc .= $key . $value;

                // Find matching translation
                $trans = "";
                // $lastMatchCursor = 0;
                // for ($i = $lastMatchCursor; $i < count($transLines); $i++) {
                //     // Check for matching time tag
                //     if ($key === $transLines[0]) {
                //         $lastMatchCursor = $i;
                //         $trans = $transLines[1];
                //     }
                // }

                if (!$this->isNullOrEmptyString($key) && $this->isNullOrEmptyString($trans)) { // $key is empty when it's not time tag, just metadata
                    $resultLrc .= " 【" . $trans . "】\n";
                }
                $resultLrc .= "\n";
            }

            // var_dump($resultLrc);
        }
        return $resultLrc;
    }

    private function processLrcLine($lrc) {
        $result = array();
        foreach (explode("\n", $lrc) as $line) {
            $key = substr($line, 0, 10);
            $value = substr($line, 10, strlen($line) - 10);
            if (!$this->isValidLrcTime($key)) {
                $key = "";
                $value = $line;
            }
            array_push($result, array($key => $value));
        }
        return $result;
    }

    private function isValidLrcTime($str) {
        if ($this->isNullOrEmptyString($str) || strlen($str) != 10 || $str[0] !== "[" || $str[9] != "]") {
            return FALSE;
        }
        for ($count = 1; $count < 9; $count++) {
            $ch = $str[$count];
            if ($ch !== ":" && $ch !== "." && !is_numeric($ch)) {
                return FALSE;
            }
        }
        return TRUE;
    }

    // Function for basic field validation (present and neither empty nor only white space
    private static function isNullOrEmptyString($question){
        return (!isset($question) || trim($question)==='');
    }

    private static function download($url) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
        ));
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }
}


////////////////////////// Debug ////////////////////////////////////

if ($DEBUG == true) {
   class TestObj {
        private $items;

        function __construct() {
            $this->items = array();
        }

        public function addLyrics($lyric, $id) {
            printf("</br>");
            printf("song id: %s\n", $id);
            printf("</br>");
            printf("== lyric ==\n");
            printf("%s\n", $lyric);
            printf("** END of lyric **\n\n");
        }

        public function addTrackInfoToList($artist, $title, $id, $prefix) {
            printf("</br>");
            printf("song id: %s\n", $id);
            printf("artist [%s]\n", $artist);
            printf("title [%s]\n", $title);
            printf("prefix [%s]\n", $prefix);
            printf("</br>");

            array_push($this->items, array(
                'artist' => $artist,
                'title'  => $title,
                'id'     => $id
            ));
        }

        function getItems() {
            return $this->items;
        }

        function getFirstItem() {
            if (count($this->items) > 0) {
                return $this->items[0];
            } else {
                return NULL;
            }
        }
    }

    /**
     * Main
     */
    $title = "tell your world";
    $artist = "初音ミク";
    echo "Trying to find lyrics for ['$title'] by artist ['$artist'] ...</br>";

    $testObj = new TestObj();
    $downloader = (new ReflectionClass("LudysuNetEaseLrc"))->newInstance();
    // $count = $downloader->getLyricsList($artist, $title, $testObj);
    // if ($count > 0) {
    //     $item = $testObj->getFirstItem();

    //     if (array_key_exists('id', $item)) {
            $downloader->getLyrics($item['id'], $testObj);
    //     } else {
    //         echo "\nno id to query lyric\n";
    //     }
    // } else {
    //     echo " ****************************\n";
    //     echo " *** Failed to find lyric ***\n";
    //     echo " ****************************\n";
    // }
}


