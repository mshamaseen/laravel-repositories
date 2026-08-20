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

Duplicate reads are cheap: the [model](Model.md) caches identical SELECTs for the lifetime of the request, so
fetching the same row in a policy and again in the controller normally costs one query.

!!! Warning
    That cache is flushed by every write and is skipped inside transactions and for locking reads — see
    [CachePerRequest](Model.md#cacheperrequest) for the exact contract before relying on it.
