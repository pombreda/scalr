<?
	class DataForm
	{
		/**
		 * Form fields. Array of DataForm objects.
		 *
		 * @var array
		 */
		protected $Fields;
		
		/**
		 * Inline help that will appear in yellow box above the form, if Inline help is enabled in registrar/registant CP.
		 *
		 * @var string
		 */
		protected $InlineHelp; 
		
		/**
		 * Returns Inline help that will appear in yellow box above the form, if Inline help is enabled in registrar/registant CP.
		 *
		 * @return unknown
		 */
		function GetInlineHelp()
		{
			return $this->InlineHelp;
		}
		
		/**
		 * Set Inline help that will appear in yellow box above the form, if Inline help is enabled in registrar/registant CP.
		 *
		 * @param string $inline_help
		 */
		function SetInlineHelp($inline_help)
		{
			$this->InlineHelp = $inline_help;
		}
		
		/**
		 * Append form field.
		 * @param FormField
		 */
		function AppendField(DataFormField $field)
		{
			if ($field instanceof DataFormField)
				$this->Fields[$field->Name] = $field;
			else 
				throw new Exception(_("Field must be an instance of DataFormField"));
		}
		
		/**
		 * Returns array of form fields (DataFormField objects); 
		 * @return array
		 */
		function ListFields()
		{
			return $this->Fields;
		}
		
		/**
		 * Clear fields
		 *
		 */
		function ClearFields()
		{
			$this->Fields = array();
		}
		
		/**
		 * Return field object by name
		 * @return DataFormField
		 */
		function GetFieldByName($name)
		{
			return (isset($this->Fields[$name])) ? $this->Fields[$name] : null;
		}
	}

?>