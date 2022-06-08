<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    private $success = 200; // successfully
    private $unauthorised = 401; // unauthorised
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }
    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|confirmed|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json(["status" => $this->unauthorised, "message" => "Please Input Valid Data", "errors" => $validator->errors()]);
        }
        $user_status = User::where("email", $request->email)->first();
        if (!is_null($user_status)) {
            return response()->json(["status" => $this->success, "success" => false, "message" => "Whoops! Email already registered"]);
        }
        $user = $this->create($request->all());
        if (!is_null($user)) {
            return response()->json(["status" => $this->success, "success" => true, "message" => "Registration completed successfully", "data" => $user]);
        } else {
            return response()->json(["status" => $this->unauthorised, "success" => false, "message" => "Failed to register"]);
        }
    }
    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(["status" =>  $this->unauthorised, "validation_error" => $validator->errors()]);
        }

        $email_status = User::where("email", $request->email)->first();
        if (!is_null($email_status)) {
            if (Hash::check($request->password, $email_status->password)) {
                $credentials = $request->only('email', 'password');
                if ($token = Auth::attempt($credentials)) {
                    return $this->createNewToken($token);
                }
            } else {
                return response()->json(["status" => $this->unauthorised, "success" => false, "message" => "Unable to login. Incorrect password."]);
            }
        } else {
            return response()->json(["status" => $this->unauthorised, "success" => false, "message" => "Email doesnt exist."]);
        }
    }

    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    protected function guard()
    {
        return Auth::guard();
    }
    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'User successfully signed out']);
    }
    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->createNewToken(auth()->refresh());
    }
    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile()
    {
        return response()->json(auth()->user());
    }
    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'status' => $this->success,
            'success' => true,
            'message' => 'You have logged in successfully',
            'data' => auth()->user()
        ]);
    }
}
