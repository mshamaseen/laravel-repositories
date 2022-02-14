# Abstract Repository

Repository is the intermediary between the model layers and the controller, it holds all the business logic and includes all the subsequent actions of an event, such as sending mail/notification, communicating to services to notify third parties, or/and communicating the model for database action.

## Scopes

The Abstract repository contains pre-defined methods that are called from the controller, If you have a case where you want to use one of these pre-defined methods but you want to include a query call to it you can add that via scopes:

```
    public function customMethod()
    {
        return $this->scope(fn($builder) => $builder->groupBy('name'))->paginate();    
    }
```

Feel free to go through the Abstract Repository methods and properties, they are self-explanatory.
