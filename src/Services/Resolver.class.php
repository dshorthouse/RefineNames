<?php
/**
 * RefineNames - use scientific names services in Open Refine
 *
 * PHP Version >= 5.5
 *
 * @author    David P. Shorthouse <davidpshorthouse@gmail.com>
 * @copyright 2014 Université de Montréal
 * @link      http://github.com/dshorthouse/RefineNames
 * @license   MIT, https://github.com/dshorthouse/RefineNames/blob/master/LICENSE
 * @package   RefineNames
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 *
 */
namespace RefineNames;

use RefineNames\ReconciliationService as Reconciliation;

 /**
  * Refine names against the Global Names Resolver web service, http://resolver.globalnames.org/api
  *
  * @package RefineNames
  * @author  David P. Shorthouse <davidpshorthouse@gmail.com>
  */
class Resolver extends Reconciliation
{
    private $_data_sources = array(
        1 => "Catalogue of Life",
        2 => "Wikispecies",
        3 => "ITIS",
        4 => "NCBI",
        5 => "Index Fungorum",
        6 => "GRIN Taxonomy for Plants",
        7 => "Union 4",
        8 => "Interim Register of Marine and Nonmarine Genera",
        9 => "WoRMS",
        10 => "Freebase",
        11 => "GBIF Backbone Taxonomy",
        12 => "EOL",
        93 => "Passiflora vernacular names",
        94 => "Inventory of Fish Species in the Wami River Basin",
        95 => "Pheasant Diversity and Conservation in the Mt. Gaoligonshan Region",
        96 => "Finding Species",
        97 => "Birds of Lindi Forests Plantation",
        98 => "Nemertea",
        99 => "Kihansi Gorge Amphibian Species Checklist",
        100 => "Mushroom Observer",
        101 => "TaxonConcept",
        102 => "Amphibia and Reptilia of Yunnan",
        103 => "Common names of Chilean Plants",
        104 => "Invasive Species of Belgium",
        105 => "ZooKeys",
        106 => "COA Wildlife Conservation List",
        107 => "AskNature",
        108 => "China: Yunnan, Southern Gaoligongshan, Rapid Biological Inventories Repor...",
        109 => "Native Orchids from Gaoligongshan Mountains, China",
        110 => "Illinois Wildflowers",
        112 => "Coleorrhyncha Species File",
        113 => "/home/dimus/files/dwca/zoological names.zip",
        114 => "Peces de la zona hidrogeográfica de la Amazonia, Colombia (Spreadsheet)",
        115 => "Eastern Mediterranean Syllidae",
        116 => "Gaoligong Shan Medicinal Plants Checklist",
        117 => "birds_of_tanzania",
        118 => "AmphibiaWeb",
        119 => "tanzania_plant_sepecimens",
        120 => "Papahanaumokuakea Marine National Monument",
        121 => "Taiwanese IUCN species list",
        122 => "BioPedia",
        123 => "AnAge",
        124 => "Embioptera Species File",
        125 => "Global Invasive Species Database",
        126 => "Sendoya S., Fernández F. AAT de hormigas (Hymenoptera: Formicidae) del Ne...",
        127 => "Flora of Gaoligong Mountains",
        128 => "ARKive",
        129 => "True Fruit Flies (Diptera, Tephritidae) of the Afrotropical Region",
        130 => "3i - Typhlocybinae Database",
        131 => "CATE Sphingidae",
        132 => "ZooBank",
        133 => "Diatoms",
        134 => "AntWeb",
        135 => "Endemic species in Taiwan",
        136 => "Dermaptera Species File",
        137 => "Mantodea Species File",
        138 => "Birds of the World: Recommended English Names",
        139 => "New Zealand Animalia",
        140 => "Blattodea Species File",
        141 => "Plecoptera Species File",
        142 => "/home/dimus/files/dwca/clemens.zip",
        143 => "Coreoidea Species File",
        144 => "Freshwater Animal Diversity Assessment - Normalized export",
        145 => "Catalogue of Vascular Plant Species of Central and Northeastern Brazil",
        146 => "Wikipedia in EOL",
        147 => "Database of Vascular Plants of Canada (VASCAN)",
        148 => "Phasmida Species File",
        149 => "OBIS",
        150 => "USDA NRCS PLANTS Database",
        151 => "Catalog of Fishes",
        152 => "Aphid Species File",
        153 => "The National Checklist of Taiwan",
        154 => "Psocodea Species File",
        155 => "FishBase",
        156 => "3i - Typhlocybinae Database",
        157 => "Belgian Species List",
        158 => "EUNIS",
        159 => "CU*STAR",
        161 => "Orthoptera Species File",
        162 => "Bishop Museum",
        163 => "IUCN Red List of Threatened Species",
        164 => "BioLib.cz",
        165 => "Tropicos - Missouri Botanical Garden",
        166 => "nlbif",
        167 => "The International Plant Names Index",
        168 => "Index to Organism Names",
        169 => "uBio NameBank",
        170 => "Arctos",
        171 => "Checklist of Beetles (Coleoptera) of Canada and Alaska. Second Edition."
    );
    
    private $_batch_size;
    private $_hit_limit;

    function __construct($batch_size = 50, $hit_limit = 5)
    {
        $this->_batch_size = $batch_size;
        $this->_hit_limit = $hit_limit;

        $this->dataSource = "";
        $this->name = 'Global Names Resolver';

        if (isset($_GET['data_source_ids'])) {
            $this->dataSource = $_GET['data_source_ids'];
            $titles = array();
            foreach (explode("|", $this->dataSource) as $key) {
                $titles[] = $this->_data_sources[$key];
            }
            $this->name = implode(" & ", $titles);
        }

        $this->identifierSpace  = 'http://resolver.globalnames.org';

        // Freebase object
        $this->schemaSpace      = 'http://rdf.freebase.com/ns/type.object.id';

        $this->types();

        $view_url = 'http://gni.globalnames.org/name_strings/{{id}}';
        $preview_url = 'http://gni.globalnames.org/name_strings/{{id}}';

        if ($view_url != '') {
            $this->view($view_url);
        }
        if ($preview_url != '') {
            $this->preview($preview_url);
        }
    }

    public function query($queries)
    {
        $q = json_decode(stripcslashes($queries));

        $this->result = new \stdClass;

        $names = array();
        foreach ($q as $query_key => $query) {
            $this->result->{$query_key} = new \stdClass;
            $this->result->{$query_key}->result = array();
            $names[] = $query_key . "|" . $query->query;
        }
        $url = 'http://resolver.globalnames.org/name_resolvers.json';
        $limit = 15;

        $chunks = array_chunk($names, $this->_batch_size);
        foreach ($chunks as $chunk) {
            $json = $this->post($url, array("data_source_ids" => $this->dataSource, "data" => implode("\n", $chunk)));
            $obj = json_decode($json);
            foreach ($obj->data as $data) {
                if (!isset($data->results)) {
                    unset($this->result->{$data->supplied_id}->result);
                } else {
                    $n = min($this->_hit_limit, count($data->results));
                    for ($i = 0; $i < $n; $i++) {
                        $hit = new \stdClass;
                        $hit->match = ($data->results == 1);
                        $hit->name  = $data->results[$i]->name_string;
                        $hit->id    = $data->results[$i]->gni_uuid;
                        $hit->score = 100*$data->results[$i]->score;
                        $this->store_hit($data->supplied_id, $hit);
                    }
                }
            }
        }

        return $this->result;
    }

}