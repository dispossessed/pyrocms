<?php if (!defined('BASEPATH')) exit('No direct script access allowed.');

class MY_Form_validation extends CI_Form_validation
{
    private $use_nonce = FALSE;

	function MY_Form_validation()
	{
		parent::CI_Form_validation();
		$this->CI->load->language('extra_validation');
	}

    /**
     * Create a new unique nonce, save it to the current session and return it.
     */
    public function create_nonce() {
        $nonce = md5(rand() . $this->CI->input->ip_address() . microtime());
        $this->CI->session->set_userdata('nonce', $nonce);
        return $nonce;
    }

    public function has_nonce() {
        return $this->use_nonce;
    }

    public function run($group = '') {
        $this->use_nonce = TRUE;
        $this->set_rules('nonce', 'Nonce', 'required|valid_nonce');
        $result = parent::run($group);
        if($result === true) {
            $this->save_nonce();
        }
        return $result;
    }

    /**
     * Mark the nonce sent from the form as already used.
     */
    private function save_nonce() {
        $this->CI->session->set_userdata('old_nonce', $this->set_value('nonce'));
    }

    /**
     * Set form validation rules for the nonce.
     */
    function nonce() {
        $this->use_nonce = true;
        $this->set_rules('nonce', 'Nonce', 'required|valid_nonce');
    }

    /**
     * Make sure the nonce is valid.
     */
    function valid_nonce($str) {
        return ($str == $this->CI->session->userdata('nonce') &&
                $str != $this->CI->session->userdata('old_nonce'));
    }

	/**
	 * Alpha-numeric with underscores dots and dashes
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function alpha_dot_dash($str)
	{
		return ( ! preg_match("/^([-a-z0-9_\-\.])+$/i", $str)) ? FALSE : TRUE;
	}

	/**
	 * Checks that a surname is valid
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function surname($str)
	{
		return ( ! preg_match("/^([-a-z\'0-9_-])+$/i", $str)) ? FALSE : TRUE;
	}

	// ------------------------------------------------------------------------

	/**
	 * CRSF Library from Kyle Hasegawa
	 * http://blog.kylehasegawa.com/codeigniter-csrf-xsrf-library
	 */

	 /**
	  * Run
	  *
	  * Validates the CSRF token then runs normal validation.
	  * 
	  * @param	string	The validation group name
	  * @param	bool	If CSRF tokens should be used
	  * @return	bool
	  */
	public function run($group = '', $csrf_enabled = TRUE)
	{
		log_message('debug', 'My_Form_validation::run() called');

		// Do we even have any data to process?  Mm?
		if (count($_POST) == 0)
		{
			return FALSE;
		}

		if($csrf_enabled)
		{
			$this->_validate_token();
		}

		return parent::run($group);
	}

	/**
	 * Validate Token
	 * 
	 * Validates the token sent from POST
	 *
	 * @access    private
	 * @return    void
	 */
	private function _validate_token()
	{
		log_message('debug', 'My_Form_validation::_validate_token() called');

		// We only load the library if we are actually using the token
		$this->CI->load->library('csrf');

		// Form ID and token from the POST input
		$form_id = $this->CI->input->post('form_id');
		$token = $this->CI->input->post('token');

		// Validate token from POST
		if ( ! $this->CI->csrf->validate_token($form_id, $token))
		{
			log_message('debug', 'My_Form_validation::_validate_token() bad token');

			// Create a new token and set the error
			$this->CI->csrf->create_token();
			$this->_error_array[] = $this->CI->lang->line('csrf_bad_token');
		}

		// Token is fine. Save it for reuse in case other validation tests fail
		else
		{
			$this->CI->csrf->save_token($form_id, $token);
		}
	}

}

/* End of file MY_Form_validation.php */