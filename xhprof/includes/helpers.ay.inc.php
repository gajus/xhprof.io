<?php
namespace ay;

/**
 * @author Gajus Kuizinas <g.kuizinas@anuary.com>
 * @version 1.0.3 (2012 06 19)
 */
function ay()
{
	if(ob_get_level())
	{
		ob_clean();
	}
    
	if(!headers_sent())
	{
		header('Content-Type: text/plain');
	}
	
	if(!DEBUG)
	{
		echo 'The requested content is inaccessible. Please try again later.';
		
		exit;
	}
	
	// unless something went really wrong, $trace[0] will always reference call to ay()
	$trace	= debug_backtrace();
	$trace	= array_shift($trace);
	
	ob_start();
	echo 'ay\ay() called in ' . $trace['file'] . ' (' . $trace['line'] . ').' . PHP_EOL . PHP_EOL;
	
	call_user_func_array('var_dump', func_get_args());
	
	echo PHP_EOL . 'Backtrace:' . PHP_EOL . PHP_EOL;
	
	debug_print_backtrace();
	echo str_replace(realpath(BASE_PATH . '/..'), '[xhprof.io]', ob_get_clean());
	
	exit;
}

/**
 * @author Gajus Kuizinas <g.kuizinas@anuary.com>
 * @version 1.4.1 (2012 12 04)
 */
function message($message, $type = 'error')
{
	$messages_types	= array('error', 'important', 'notice', 'success');
	
	if(!in_array($type, $messages_types))
	{
		throw new HelperException('Invalid message type.');
	}
	
	$message		= array('type' => $type, 'message' => $message);
	
	if(!empty($_SESSION['ay']['flash']['messages']) && in_array($message, $_SESSION['ay']['flash']['messages']))
	{
		return FALSE;
	}
	
    $_SESSION['ay']['flash']['messages'][]	= $message;
}

/**
 * @author Gajus Kuizinas <g.kuizinas@anuary.com>
 * @version 2.3.1 (2012 12 04)
 */
function display_messages()
{
	static $already_displayed	= FALSE;
	
	if($already_displayed)
	{
		return;
	}
	
	$already_displayed	= TRUE;

    $messages_types		= array('error', 'important', 'notice', 'success');
	
	$return	= '';
	
    if(!empty($_SESSION['ay']['flash']['messages']))
    {
    	foreach($_SESSION['ay']['flash']['messages'] as $m)
		{
			if(empty($m['type']))
			{
				unset($_SESSION['ay']['flash']['messages']);
			
				throw new HelperException('Missing message type.');
			}
			else if(!in_array($m['type'], $messages_types))
			{
				unset($_SESSION['ay']['flash']['messages']);
			
				throw new HelperException('Invalid message type.');
			}
			else if(empty($m['message']))
			{
				unset($_SESSION['ay']['flash']['messages']);
			
				throw new HelperException('Message cannot be empty.');
			}
		
			$return .= '<li class="' . $m['type'] . '">' . $m['message'] . '</li>';
		}
		
		$return	= '<ul>' . $return . '</ul>';
    }
    
	return '<div class="ay-message-placeholder">' . $return . '</div>';
}

/**
 * @author Gajus Kuizinas <g.kuizinas@anuary.com>
 * @version 1.5.0 (2012 11 24)
 */
function error_present()
{
	return !empty($_SESSION['ay']['flash']['messages']) && count(array_filter($_SESSION['ay']['flash']['messages'], function($e){ return $e['type'] === 'error'; }));
}

/**
 * @author Gajus Kuizinas <g.kuizinas@anuary.com>
 * @version 1.0.7 (2012 10 15)
 */
function redirect($url = REDIRECT_REFERRER, $message_text = NULL, $message_type = 'error')
{
	// If there aren't any error, then clear the persistent user input.
	if(!error_present())
	{
		unset($_SESSION['ay']['flash']['input']);
	}

	if($message_text !== NULL)
    {
		message($message_text, $message_type);
    }
    
    if(headers_sent())
	{
		throw new HelperException('Redirect failed. Headers already sent.');
	}

    if($url === REDIRECT_REFERRER)
    {
		$url	= empty($_SERVER['HTTP_REFERER']) ? constant('URL_' . mb_strtoupper(INTERFACE_END)) : $_SERVER['HTTP_REFERER'];
    }
    elseif(strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0)
    {
    	$url	= rtrim(constant('URL_' . mb_strtoupper(INTERFACE_END)), '/') . '/' . $url;
    }

    header('Location: ' . $url);

	exit;
}

/**
 * @author Gajus Kuizinas <g.kuizinas@anuary.com>
 * @version 1.6.7 (2012 11 01)
 */
function input($name, $label, array $input_options = NULL, array $row_options = NULL, array $return_options = NULL)
{
	global $input;
	
	// all input generated using input() is sent through $_POST['ay'] array
	$name						= strpos($name, '[') !== FALSE ? 'ay[' . strstr($name, '[', TRUE) . ']' . strstr($name, '[') : 'ay[' . $name . ']';
	$original_name_path			= explode('][', mb_substr($name, 3, -1));
	
	// default to a text field if the type is not defined
	if(empty($input_options['type']))
	{
		$input_options['type']	= empty($input_options['options']) ? 'text' : 'select';
	}
	
	$input_options['name']		= $name;
	
	// get input value
	$default_value				= FALSE;
	
	if($input_options['type'] != 'password')
	{		
		$value					= empty($_SESSION['ay']['flash']['input']) ? $input : $_SESSION['ay']['flash']['input'];
		
		foreach($original_name_path as $key)
		{
			if(!is_array($value) || !array_key_exists($key, $value))
			{
				$value	= FALSE;
				
				break;
			}
			
			$value	= $value[$key];
		}
		
		if($value === FALSE || is_array($value))
		{
			$default_value		= TRUE;
		
			$value				= isset($input_options['value']) ? $input_options['value'] : NULL;
		}
	}
	
	// generate attribute string
	$allowed_attributes			= array('name', 'id', 'class', 'maxlength', 'autocomplete');
	
	if(in_array($input_options['type'], array('text', 'textarea')))
	{
		$allowed_attributes[]	= 'placeholder';
		$allowed_attributes[]	= 'readonly';
	}
	
	$input_attr_str				= '';
	
	foreach($input_options as $k => $v)
	{
		if(in_array($k, $allowed_attributes))
		{
			$input_attr_str	.= ' ' . $k . '="' . $v . '"';
		}			
	}
	
	$str	= array
	(
		'append'	=> '',
		'class'		=> implode('-', $original_name_path) . '-input'
	);
	
	// generate input string
	switch($input_options['type'])
	{
		case 'select':
			if(empty($input_options['options']))
			{
				throw new HelperException('Select input is missing options array.');
			}
		
			$option_str	= '';
		
			foreach($input_options['options'] as $v => $l)
			{
				$option_str	.= '<option value="' . $v . '"' . ($value == $v ? ' selected="selected"' : '') . '>' . $l . '</option>';
			}
		
			$str['input']	= '<select ' . $input_attr_str . '>' . $option_str . '</select>';
		
			break;
			
		case 'checkbox':
			if(!array_key_exists('value', $input_options))
			{
				$input_options['value']	= 1;
			}
			else
			{
				$input_options['value']	= (int) $input_options['value'];
			}			
		
			$str['input']				= '<input type="checkbox" value="' . $input_options['value'] . '"' . $input_attr_str . '' . (!$default_value && $input_options['value'] == $value ? ' checked="checked"' : '') . ' />';
			break;
		
		case 'radio':
			if(!array_key_exists('value', $input_options))
			{
				throw new HelperException('Radio input is missing value parameter.');
			}
			
			$input_options['value']		= (int) $input_options['value'];
			
			$str['input']				= '<input type="radio" value="' . $input_options['value'] . '"' . $input_attr_str . '' . (!$default_value && $input_options['value'] == $value ? ' checked="checked"' : '') . ' />';

			break;
		
		case 'textarea':
			$str['input']	= '<textarea' . $input_attr_str . '>' . filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS) . '</textarea>';
			break;
		
		case 'password':
			$str['input']	= '<input type="password" ' . $input_attr_str . ' />';
			break;
		
		default:
			$str['input']	= '<input type="' . $input_options['type'] . '" value="' . filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS) . '"' . $input_attr_str . ' />';
			break;
	}
	
	$return_options['return']	= empty($return_options['return']) ? 'row' : $return_options['return'];
	
	switch($return_options['return'])
	{
		case 'row':			
			if(!empty($row_options['comment']))
			{
				$str['append']	.= '<div class="comment">' . $row_options['comment'] . '</div>';
			}
			
			if(!empty($row_options['class']))
			{
				$str['class']	.= ' ' . $row_options['class'];
			}
			
			if($label === NULL)
			{
				$str['body']	= $str['input'];
			}
			else
			{
				$input_label	= in_array('inverse', explode(' ', $str['class'])) ? $str['input'] . '<div class="label">' . $label . '</div>' : '<div class="label">' . $label . '</div>' . $str['input'];
				
			
				$str['body']	= '<label class="row ' . $str['class']  . ' input-' . $input_options['type'] . '">' . $input_label . '</label>';
			}			
			
			$str['return']	= $str['body'] . ' ' . $str['append'];
			
			break;
			
		case 'input':
			$str['return']	= $str['input'];
			break;
			
		default:
			throw new HelperException('input(); unknown return type `' . $return_options['return'] . '`.');
			break;
	}
	
	return $str['return'];
}

/**
 * @author Gajus Kuizinas <g.kuizinas@anuary.com>
 * @version 1.0.4 (2012 06 19); adapted to XHProf.io
 */
function error_exception_handler()
{
	$args	= func_get_args();
	
	if(func_num_args() == 1)
	{
		$data	= array
		(
			'type'				=> NULL,
			'message'			=> $args[0]->getMessage(),
			'file'				=> $args[0]->getFile(),
			'line'				=> $args[0]->getLine()
		);
	}
	else
	{		
		$data	= array
		(
			'type'				=> $args[0],
			'message'			=> $args[1],
			'file'				=> $args[2],
			'line'				=> $args[3]
		);
	}
	
	if(ob_get_level())
	{
		ob_clean();
	}

	if(!headers_sent())
	{
		header('Content-Type: text/plain');
		
		header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 500 Internal Server Error');
	}
	
	if(DEBUG)
	{
		if($data['type'] === NULL)
		{
			$error_type	= get_class($args[0]);
		}
		else
		{
			switch($data['type'])
			{
				case E_ERROR:
				case E_USER_ERROR:
					$error_type	= 'Fatal run-time error.';
					break;
					
				case E_WARNING:
				case E_USER_WARNING:
					$error_type	= 'Run-time warnings (non-fatal error).';
					break;
					
				case E_NOTICE:
				case E_USER_NOTICE:
					$error_type	= 'Run-time notice.';
					break;
					
				default:
					$error_type	= 'Unknown ' . $data['type'] . '.';
					break;
			}
		}
		
		ob_start();
		echo "Type:\t\t{$error_type}\nMessage:\t{$data['message']}\nFile:\t\t{$data['file']}\nLine:\t\t{$data['line']}\nTime:\t\t" . date(FORMAT_DATETIME) . "\n\n";
    	
    	debug_print_backtrace();
    	echo str_replace(realpath(BASE_PATH . '/..'), '[xhprof.io]', ob_get_clean());
	}
	else
	{		
		echo 'Unexpected system behaviour.';
	}
	
	if(function_exists('fastcgi_finish_request'))
	{
		fastcgi_finish_request();
	}
	
	return FALSE;
}

class HelperException extends \Exception {}