<?php
use Illuminate\Support;  // https://laravel.com/docs/5.8/collections - provides the collect methods & collections class
use LSS\Array2Xml;
require_once('classes/Exporter.php');

class Controller {

    public function __construct($args) {
        $this->args = $args;
        $this->availableTypes = [
            'players', 
            'playerstats'
        ];//available types which are saved in exporter

        $this->searchArgs = [
            'player',
            'playerId',
            'team',
            'position',
            'country'
        ];//available fields to search
    }

    public function export($args){
        $data = [];        
        $type = $args->get('type');
        if(!$type)
            exit("Please specify a type!");

        $searchType = in_array($type, $this->availableTypes);//validate if the type is available
        if(!$searchType)
            exit("Error: Type \"$type\" is not available!");

        return $this->getExport($args);
    }

    /**
     * Get Query from exporter
     */
    private function getExport($args){        
        $type        = $args->pull('type');//this field will not be validated
        $format      = $args->pull('format') ?: 'html';//this field will not be validated

        $this->validateArgs($args);//validate fields to search
        
        $getExporter = "get$type";//get the function in Exporter.php
        $exporter    = new Exporter($args);
        $data        = $exporter->$getExporter();

        return $exporter->format($data, $format);
    }

    /**
     * Validates if the search field is available
     */
    private function validateArgs($args){
        foreach($args as $key => $value)
            if(!in_array($key, $this->searchArgs))
                exit("Error: You can't search \"$key\"");
    }
}