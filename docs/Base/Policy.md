# Policy

Policy files are the holders for authorization in your application.

They act exactly the same way as [Laravel policy](https://laravel.com/docs/authorization) but with automatic trigger base on the controller method name and without the need for registration.

For example, to validate a method called `documents` in the controller, all you need to do is to make another method called `documents` in the policy file.

```
function documents(?User $user)
{
    // your authorization logic here.
}
```

!!! Note
    Remember to add `?` before the User if you want to skip auth authorization. 

We don't pass the model to policy methods as a parameter, instead you should make whatever query needed in your policy.

Don't worry about query duplications, we already implemented a cache on the [model](Model.md) side.
