<?php
use Illuminate\Support;
use LSS\Array2Xml;

// retrieves & formats data from the database for export
class Exporter {
    public function __construct($args){
        $this->table = 'roster';

        /**
         * search fields and column name mapping
         * seach field => database column name
         * search fields should register here
         */
        $this->column = [
            'player'   => 'name',
            'playerId' => 'id',
            'team'     => 'team_code',
            'position' => 'pos',
            'country'  => 'nationality'
        ];

        $this->where = [];//initiate where condition var
        $this->queryConditions($args);//get where conditions
    }

    //get query conditions
    function queryConditions($search){
        foreach($search as $key => $value){
            $column = $this->validateColumn($key);
            if($column !== false)
                $this->where[] = "$this->table.$column = \"$value\"";
        }

        return $this->where = implode(' AND ', $this->where);
    }

    //validates column if existing
    function validateColumn($search){
        foreach($this->column as $key => $value)
            if($search === $key)
                return $value;

        return false;//return false if field is not existed
    }

    function getplayerstats() {
        $where = $this->where;
        $sql   = "SELECT roster.name, player_totals.*
            FROM player_totals
                INNER JOIN roster ON (roster.id = player_totals.player_id)
            WHERE $where";

        $data = query($sql) ?: [];

        // calculate totals
        foreach ($data as $row) {
            unset($row['player_id']);
            $row['total_points'] = ($row['3pt'] * 3) + ($row['2pt'] * 2) + $row['free_throws'];
            $row['field_goals_pct'] = $row['field_goals_attempted'] ? (round($row['field_goals'] / $row['field_goals_attempted'], 2) * 100) . '%' : 0;
            $row['3pt_pct'] = $row['3pt_attempted'] ? (round($row['3pt'] / $row['3pt_attempted'], 2) * 100) . '%' : 0;
            $row['2pt_pct'] = $row['2pt_attempted'] ? (round($row['2pt'] / $row['2pt_attempted'], 2) * 100) . '%' : 0;
            $row['free_throws_pct'] = $row['free_throws_attempted'] ? (round($row['free_throws'] / $row['free_throws_attempted'], 2) * 100) . '%' : 0;
            $row['total_rebounds'] = $row['offensive_rebounds'] + $row['defensive_rebounds'];
        }
        return collect($data);
    }

    function getplayers() {
        $where = $this->where;
        $sql = "
            SELECT roster.*
            FROM roster
            WHERE $where";
        return collect(query($sql))
            ->map(function($item, $key) {
                unset($item['id']);
                return $item;
            });
    }

    public function format($data, $format = 'html'){
        // return the right data format
        switch($format){
            case 'xml':
                return $this->formatXml($data);
                break;
            case 'json':
                return $this->formatJson($data);                
                break;
            case 'csv':
                return $this->formatCsv($data);
                break;
            default: // html
                return $this->formatHtml($data);               
                break;
        }
    }

    public function formatXml($data){
        header('Content-type: text/xml');                
        // fix any keys starting with numbers
        $keyMap = ['zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine'];
        $xmlData = [];
        foreach ($data->all() as $row) {
            $xmlRow = [];
            foreach ($row as $key => $value) {
                $key = preg_replace_callback('(\d)', function($matches) use ($keyMap) {
                    return $keyMap[$matches[0]] . '_';
                }, $key);
                $xmlRow[$key] = $value;
            }
            $xmlData[] = $xmlRow;
        }
        $xml = Array2XML::createXML('data', [
            'entry' => $xmlData
        ]);
        return $xml->saveXML();
    }

    public function formatJson($data){
        header('Content-type: application/json');
        return json_encode($data->all());
    }

    public function formatCsv($data){
        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename="export.csv";');
        if (!$data->count()) {
            return;
        }
        $csv = [];
        
        // extract headings
        // replace underscores with space & ucfirst each word for a decent headings
        $headings = $this->getFormatHeading($data);
        $csv[] = $headings->join(',');

        // format data
        foreach ($data as $dataRow) {
            $csv[] = implode(',', array_values($dataRow));
        }
        return implode("\n", $csv);
    }

    public function formatHtml($data){
        if (!$data->count()) {
            return $this->htmlTemplate('Sorry, no matching data was found');
        }
        
        // extract headings
        // replace underscores with space & ucfirst each word for a decent heading
        $headings = $this->getFormatHeading($data);
        $headings = '<tr><th>' . $headings->join('</th><th>') . '</th></tr>';

        // output data
        $rows = [];
        foreach ($data as $dataRow) {
            $row = '<tr>';
            foreach ($dataRow as $key => $value) {
                $row .= '<td>' . $value . '</td>';
            }
            $row .= '</tr>';
            $rows[] = $row;
        }
        $rows = implode('', $rows);
        return $this->htmlTemplate('<table>' . $headings . $rows . '</table>');
    }

    public function getFormatHeading($data){
        $headings = collect($data->get(0))->keys();
        return $headings = $headings->map(function($item, $key) {
            return collect(explode('_', $item))
                ->map(function($item, $key) {
                    return ucfirst($item);
                })
                ->join(' ');
        });
    }

    // wrap html in a standard template
    public function htmlTemplate($html) {
        return '
<html>
<head>
<style type="text/css">
    body {
        font: 16px Roboto, Arial, Helvetica, Sans-serif;
    }
    td, th {
        padding: 4px 8px;
    }
    th {
        background: #eee;
        font-weight: 500;
    }
    tr:nth-child(odd) {
        background: #f4f4f4;
    }
</style>
</head>
<body>
    ' . $html . '
</body>
</html>';
    }
}

?>