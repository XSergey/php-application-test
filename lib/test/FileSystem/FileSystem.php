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
}