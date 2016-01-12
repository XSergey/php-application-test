<?php
namespace Test;

class FileSystem
{
    public function exists($file)
	{
		return file_exists($file);
	}
    
    public function includeFile($file)
	{
		return include $file;
	}
    
    public function getContents($file)
	{
		return file_get_contents($file);
	}
    
    public static function putContents($file, $data, $lock = false)
	{
		return file_put_contents($file, $data, $lock ? LOCK_EX : 0);
	}
    
    public function lastModified($file)
	{
		return filemtime($file);
	}
}