<?php

$DEBUG = false;
$NEED_TRANSLATION = false;


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
        //TODO combine translated lrc
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
        $response = $this->download($url);
        if ($this->isNullOrEmptyString($response)) {
            return NULL;
        }

        $json = json_decode($response, true);
        return $json['lrc']['lyric'];
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
    $count = $downloader->getLyricsList($artist, $title, $testObj);
    if ($count > 0) {
        $item = $testObj->getFirstItem();

        if (array_key_exists('id', $item)) {
            $downloader->getLyrics($item['id'], $testObj);
        } else {
            echo "\nno id to query lyric\n";
        }
    } else {
        echo " ****************************\n";
        echo " *** Failed to find lyric ***\n";
        echo " ****************************\n";
    }
}


