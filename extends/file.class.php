<?php
Class file extends LessCacheer
{
    /**
     * Returns the last modified date of a cache file
     *
     * @param $file
     * @return int
     */
    function modified($file)
    {
        return (file_exists($file)) ? (int) filemtime($file) : 0;
    }
    
    function cache($input, $type = 'user', $force_recache = false)
    {
        $alias_cache     = self::make_alias($input); // path relative to the file
        $basename        = basename($input); // filename
        $cached_filename = DataCache::getFilename($alias_cache, $basename);
        if (self::modified($cached_filename) < self::modified($input) + $this->conf['cachetime']) {
            @unlink($cached_filename);
            $this->recache = true;
        }
        if ($type == 'mixins') {
            if (!in_array(dirname($input).'/', $this->conf['less_options']['importDir'])) {
                $this->conf['less_options']['importDir'][] = dirname($input).'/';
            }
            $data                                      = "@import '$basename';";
            $this->mixin_imported++;
        } else if (!$data = DataCache::Get($alias_cache, $basename)) {
            $data = file_get_contents($input);
            DataCache::Put($alias_cache, $basename, $this->conf['cachetime'], $data); // put data inside the cache
        }
        return $data;
    }
    
    function make_alias($str)
    {
        $str = str_replace(array(
            '//',
            ',',
            $this->conf['origin_install'],
            '.less',
            '/'
        ), array(
            '/',
            '-',
            '',
            '',
            '_'
        ), $str);
        $str .= '_'.$this->compression_id;
        return $str;
    }
}