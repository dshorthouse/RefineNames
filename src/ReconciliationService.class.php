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

/**
 * Base class for reconciliation services
 *
 * @package RefineNames
 * @author  David P. Shorthouse <davidpshorthouse@gmail.com>
 */
abstract class ReconciliationService
{
    abstract function query($queries);

    protected $name;
    protected $identifierSpace;
    protected $schemaSpace;
    protected $defaultTypes = array();
    protected $result;
    protected $width = 430;
    protected $height = 300;

    function __construct()
    {
        $this->name             = '';
        $this->identifierSpace  = '';
        $this->schemaSpace      = 'http://rdf.freebase.com/ns/type.object.id'; // FreeBase object id
        $this->types();
    }

    public static function get($url, $userAgent = '')
    {
        $data = '';
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_FOLLOWLOCATION,   1);
        curl_setopt ($ch, CURLOPT_COOKIEJAR, 'cookie.txt');

        if ($userAgent != '') {
            curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        }

        $curl_result = curl_exec($ch);

        if (curl_errno ($ch) != 0 ) {
            echo "CURL error: ", curl_errno ($ch), " ", curl_error($ch);
        } else {
            $info = curl_getinfo($ch);
            $http_code = $info['http_code'];
            if (self::http_code_valid($http_code)) {
                $data = $curl_result;
            }
        }
        return $data;
    }

    public static function post($url, $data = array(), $userAgent = '')
    {
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        if ($userAgent != '') {
            curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        }

        $curl_result = curl_exec($ch);

        if (curl_errno ($ch) != 0 ) {
            echo "CURL error: ", curl_errno ($ch), " ", curl_error($ch);
        } else {
            $info = curl_getinfo($ch);
            $http_code = $info['http_code'];
            if (self::http_code_valid($http_code)) {
                $data = $curl_result;
            }
        }
        return $data;
    }

    public static function http_code_valid($http_code)
    {
        if ( ($http_code == '200') || ($http_code == '302') || ($http_code == '403')) {
            return true;
        } else {
            return false;
        }
    }

    protected function view($url)
    {
        $this->view = new \stdClass;
        $this->view->url = $url;
    }

    protected function preview($url)
    {
        $this->preview = new \stdClass;
        $this->preview->url = $url;
        $this->preview->width = $this->width;
        $this->preview->height = $this->height;
    }

    protected function types()
    {
        $type = new \stdClass;
        $type->id = '/biology/organism_classification/scientific_name';
        $type->name = 'Scientific name';
        $this->defaultTypes[] = $type;
    }

    protected function metadata()
    {
        $metadata = new \stdClass;
        $metadata->name             =  $this->name;
        $metadata->identifierSpace  =  $this->identifierSpace;
        $metadata->schemaSpace      =  $this->schemaSpace;

        if (isset($this->view)) {
            $metadata->view =  $this->view;
        }
        if (isset($this->preview)) {
            $metadata->preview =  $this->preview;
        }
        if (isset($this->defaultTypes)) {
            $metadata->defaultTypes =  $this->defaultTypes;
        }

        return $metadata;
    }

    protected function store_hit($query_key, $hit)
    {
        $hit->type[] = $this->defaultTypes[0];
        $this->result->{$query_key}->result[] = $hit;
    }

    public function call($parameters)
    {
        $queries = '';
        $callback = '';
        $output = '';

        if (isset($parameters['queries'])) {
            $queries = $parameters['queries'];
        }

        if (isset($parameters['callback'])) {
            $callback = $parameters['callback'];
        }

        $result = ($queries == '') ? $this->metadata() : $this->query($queries);

        if($callback != '') {
            $output = $callback . '(';
        }
        $output .= json_encode($result, JSON_PRETTY_PRINT);
        if($callback != '') {
            $output .= ')';
        }

        header("Content-Type: application/json");
        echo $output;
    }

}