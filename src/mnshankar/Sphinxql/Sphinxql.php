<?php
namespace mnshankar\Sphinxql;
class Sphinxql
{
    protected $library;
    protected $hits;
    public function __construct(\Foolz\SphinxQL\SphinxQL $library)
    {
        $this->library = $library;
    }
    /**
     * return the library SphinxQL object for chaining other calls
     * @return \Foolz\SphinxQL\SphinxQL
     */
    public function query()
    {
        return $this->library->forge($this->library->getConnection());
    }
    /**
     * set the hits array
     * @param array $hits - the array returned by executing the SphinxQL
     * @return \mnshankar\Sphinxql\Sphinxql
     */
    public function with($hits)
    {
        $this->hits = $hits;
        return $this;
    }
    /**
     * if name is null, return id's
     * if name is class (model) return model->get()
     * if name is table return table->get()
     * @param string $name
     * @param string $key, column name that maps to matched id returned by sphinx
     * @return mixed (either array or eloquentcollection)
     */
    public function get($name=null, $sphinxKey='id', $dbKey='id', array $dbColumns=[], $respect_sort_order = true)
    {
        $matchids = array_pluck($this->hits, $sphinxKey);
        if ($name===null)
        {
            return $matchids;
        }
        if (class_exists($name))
        {
            if ( ! empty ($dbColumns))
            {
                $result = call_user_func_array($name . "::whereIn", array($dbKey, $matchids))->get($dbColumns);
            }
            else
            {
                $result = call_user_func_array($name . "::whereIn", array($dbKey, $matchids))->get();
            }
        }
        else
        {
            if ( ! empty ($dbColumns))
            {
                $result = \DB::table($name)->whereIn($dbKey, $matchids)->get($dbColumns);
            }
            else
            {
                $result = \DB::table($name)->whereIn($dbKey, $matchids)->get();
            }
        }

        if($respect_sort_order)
        {
            if(isset($matchids))
            {
                $return_val = new \Illuminate\Database\Eloquent\Collection;

                foreach($matchids as $matchid)
                {
                    $key = $this->getResultKeyByID($matchid, $result, $dbKey);
                    $return_val->add($result[$key]);
                }
                return $return_val;
            }
        }

        return $result;
    }
    /**
     * Execute raw query against the sphinx server
     * @param string $query
     */
    public function raw( $query )
    {
       return $this->library->getConnection()->query($query);
    }

    private function getResultKeyByID($id, $result, $idKey = 'id')
    {
        if(count($result) > 0)
        {
            foreach($result as $k => $result_item)
            {
                if ( $result_item->$idKey == $id )
                {
                    return $k;
                }
            }
        }
        return false;
    }
}
