# Request Validations
Request files are used to hold the validation logic for requests. we use a Request file for each Controller.

## Controller Method Name Validation

To validate a request for a specific controller method you can create a method in the request file that has the same controller method name + Rules.

For example, when index method in the controller is called a method called indexRules will be triggered here if it is exists.

## HTTP Method Validations

to validate base on the HTTP method you can follow this syntax `{HTTP Method}MethodRules`, these methods will be triggered automatically when injected in a controller. 


!!! Note
    all validation methods **MUST** return an array.
