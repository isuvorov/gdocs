<?php


class GDocsTable
{

    private $_content;

    public function __construct($content)
    {
        $this->_content = $content;

    }

    public function getRaw()
    {
        return $this->_content;
    }


    public function getAssoc()
    {
        $header = $this->_content[0];
        $body = array_slice($this->_content, 1);

        $assoc = [];
        foreach ($body as $row) {
            $assocRow = [];
            foreach ($header as $num => $name) {
                $assocRow[$name] = $row[$num];
            }
            $assoc[] = $assocRow;
        }
        return $assoc;
    }


    public function getParsed()
    {
        return $this->getAssoc();
        $config = json_decode($this->_content[0][0], 1);
        $header = $this->_content[1];
        $body = array_slice($this->_content, 2);

        $assoc = [];
        foreach ($body as $row) {
            $assocRow = [];
            foreach ($header as $num => $name) {
                $assocRow[$name] = $row[$num];
            }
            $assoc[] = $assocRow;
        }
        return $assoc;
    }
}

class GDocs
{
    private $_url;
    private $_content;
    private $_dom;
    private $_tables;

    public function __construct($rawUrl)
    {
        $this->_gdocsId = self::getGDocsId($rawUrl);
        $this->_url = self::getHtmlUrl($this->_gdocsId);
        $this->_content = self::file_get_contents_utf8($this->_url);

        if (!$this->_content)
            throw new Exception('something wrong 1');

        $this->_dom = new DOMDocument();
        $html = @$this->_dom->loadHTML('<meta http-equiv="content-type" content="text/html; charset=utf-8">' . $this->_content);
        $this->_dom->preserveWhiteSpace = false;

        if (!$this->_dom)
            throw new Exception('something wrong 2');
        if (!$this->_dom->getElementById('sheet-menu') && !$this->_dom->getElementById('doc-title'))
            throw new Exception('something wrong 3');


        $this->_tables = [];

        if ($this->_dom->getElementById('sheet-menu') !== null) {
            $sheets_dom = $this->_dom->getElementById('sheet-menu')->getElementsByTagName('a');
            foreach ($sheets_dom as $table_id => $sheet) {
                $this->_tables[$table_id]['title'] = $sheet->textContent;
            }
        } else {
            $this->_tables[0]['title'] = $this->_dom->getElementById('doc-title')->getElementsByTagName('span')->item(0)->textContent;
        }


        $tables_dom = $this->_dom->getElementsByTagName('table');
        foreach ($tables_dom as $table_id => $table_dom) {
            $rows = $table_dom->getElementsByTagName('tbody')->item(0)->getElementsByTagName('tr');

            /**
             * @todo а что если нет ничего на странице
             */

//            if(!$rows->item(1)){
//                throw new Exception('something wrong 4');
//            }

//            $cols = $rows->item(1)->getElementsByTagName('td');
//            var_dump($cols);
            $table = array();
            foreach ($rows as $i => $row) {
                $cols = $row->getElementsByTagName('td');
                $row = array(); //
                foreach ($cols as $j => $node) {
                    $row[] = $node->textContent;
                }
                $table[] = $row;
            }
            $this->_tables[$table_id]['data'] = $table;
            $this->_tables[$table_id]['table'] = new GDocsTable($table);
        }


    }

    public static function getGDocsId($rawUrl)
    {

        return substr($rawUrl, strlen('https://docs.google.com/spreadsheets/d/'), strlen('1KPwPGZzL5FRJ6KjeCI3W7a5E3SVB5iWTvKLpfv8wAAs'));
//        var_dump($rawUrl);

    }

    public static function getCanonicalUrl($gdocsId)
    {
        return 'https://docs.google.com/spreadsheets/d/' . $gdocsId;
    }

    public static function getHtmlUrl($gdocsId)
    {
        return self::getCanonicalUrl($gdocsId) . '/pubhtml';

    }

    public function getPages()
    {

    }

    public function getPagesContent()
    {

    }


    public function getTables()
    {
        return $this->_tables;
    }

    public function getTable($page = 0)
    {
        return $this->_tables[$page]['table'];
    }

    public static function file_get_contents_utf8($fn)
    {
        $content = file_get_contents($fn);
        return mb_convert_encoding($content, 'UTF-8',
            mb_detect_encoding($content));
    }


    //
    public static function getFromUrl($url)
    {
        $file_contents = self::file_get_contents_utf8($url);


        $dom = new DOMDocument();
        $html = $dom->loadHTML('<meta http-equiv="content-type" content="text/html; charset=utf-8">' . $file_contents); //Added a @ to hide warnings - you might remove this when testing

        $dom->preserveWhiteSpace = false;


        ///
        /**
         * @todo а что если нет страниц?
         */


        $tables = array();

        if ($dom->getElementById('sheet-menu') !== null) {
            $sheets_dom = $dom->getElementById('sheet-menu')->getElementsByTagName('a');
//            $sheets[] = array();

            foreach ($sheets_dom as $table_id => $sheet) {
                $tables[$table_id]['title'] = $sheet->textContent;
            }
        } else {
            $tables[0]['title'] = $dom->getElementById('doc-title')->getElementsByTagName('span')->item(0)->textContent;
        }


        ///

        $tables_dom = $dom->getElementsByTagName('table');
        foreach ($tables_dom as $table_id => $table_dom) {
            $rows = $table_dom->getElementsByTagName('tbody')->item(0)->getElementsByTagName('tr');

            /**
             * @todo а что если нет ничего на странице
             */
            $cols = $rows->item(1)->getElementsByTagName('td'); //You'll need to edit the (0) to reflect the row that your headers are in.

            /*
                //var_dump($cols);
                $row_headers = array(); //
                foreach ($cols as $j => $node) {
                    $row_headers[] = $node->textContent;
                }
            */
            $table = array();
            foreach ($rows as $i => $row) {
                $cols = $row->getElementsByTagName('td');
                $row = array(); //
                foreach ($cols as $j => $node) {
                    $row[] = $node->textContent;
                }
                $table[] = $row;
            }
            $tables[$table_id]['data'] = $table;
        }

        return $tables;
    }

    public static function gdocs2array($url)
    {
        $file_contents = self::file_get_contents_utf8($url);
        $dom = new DOMDocument();
        $html = $dom->loadHTML('<meta http-equiv="content-type" content="text/html; charset=utf-8">' . $file_contents); //Added a @ to hide warnings - you might remove this when testing

        $dom->preserveWhiteSpace = false;


        $tables = $dom->getElementsByTagName('table');
        $rows = $tables->item(0)->getElementsByTagName('tbody')->item(0)->getElementsByTagName('tr');

        $cols = $rows->item(1)->getElementsByTagName('td'); //You'll need to edit the (0) to reflect the row that your headers are in.

        /*
            //var_dump($cols);
            $row_headers = array(); //
            foreach ($cols as $j => $node) {
                $row_headers[] = $node->textContent;
            }
        */
        $table = array();
        foreach ($rows as $i => $row) {
            $cols = $row->getElementsByTagName('td');
            $row = array(); //
            foreach ($cols as $j => $node) {
                $row[] = $node->textContent;
            }
            $table[] = $row;
        }
        return $table;

    }

    public static function humanArray2assocArray($array)
    {
        $row_headers = $array[0];
        return array_map(function ($sub_array) use ($row_headers) {
            $new_sub_array = array();

            foreach ($sub_array as $i => $cell) {
                $new_sub_array[$row_headers[$i]] = $cell;
            }
            return $new_sub_array;
        }, array_slice($array, 1));
    }
}


?>
