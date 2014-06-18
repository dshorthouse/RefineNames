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
 * Refine names against the VASCAN web service, http://data.canadensys.net/vascan/api
 *
 * @package RefineNames
 * @author  David P. Shorthouse <davidpshorthouse@gmail.com>
 */
class Vascan extends Reconciliation
{
    private $_batch_size;
    private $_hit_limit;

    function __construct($batch_size = 50, $hit_limit = 5)
    {
        $this->_batch_size = $batch_size;
        $this->_hit_limit = $hit_limit;

        $this->name             = 'Vascular Plants of Canada';
        $this->identifierSpace  = 'http://data.canadensys.net/vascan/';

        // Freebase object
        $this->schemaSpace      = 'http://rdf.freebase.com/ns/type.object.id';

        $this->types();

        $view_url = 'http://data.canadensys.net/vascan/taxon/{{id}}';
        $preview_url = 'http://data.canadensys.net/vascan/taxon/{{id}}';

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

        $url = 'http://data.canadensys.net/vascan/api/0.1/search.json';

        $chunks = array_chunk($names, $this->_batch_size);
        foreach ($chunks as $chunk) {
            $json = $this->post($url, array("q" => implode("\n", $chunk)));
            $obj = json_decode($json);
            if (isset($obj->results)) {
                foreach ($obj->results as $results) {
                    if ($results->numMatches == 0) {
                        unset($this->result->{$results->localIdentifier}->result);
                    } else {
                        $n = min($this->_hit_limit, $results->numMatches);
                        for ($i = 0; $i < $n; $i++) {
                            $hit = new \stdClass;
                            $hit->match = ($results->numMatches == 1);
                            $hit->name  = $results->matches[$i]->scientificName;
                            $hit->id    = $results->matches[$i]->taxonID;
                            similar_text($results->searchedTerm, $hit->name, $hit->score);
                            $this->store_hit($results->localIdentifier, $hit);
                        }
                    }
                }
            }
        }

        return $this->result;
    }

}