<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use App\Mail\SendMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Traits\notificationMessage;


class AuthPasswordController extends Controller
{
    use notificationMessage;
    private $success = 200; // successfully
    private $unauthorised = 401; // unauthorised

    public function changePassword(Request $request)
    {
        $userId               = Auth::user()->id;
        $newPassword          = $request->password;
        $passwordConfirmation = $request->passwordConfirmation;

        if ($newPassword == $passwordConfirmation) {
            if (Hash::check($request->currentPassword, Auth::user()->password)) {
                try {
                    DB::beginTransaction();
                    User::changePassword($userId, $newPassword);
                    DB::commit();
                    $status = true;
                } catch (\Exception $e) {
                    var_dump($e->getMessage());
                    DB::rollback();
                    $status = false;
                }
                return response()->json(($status) ? $this->successFull() : $this->failed());
            } else {
                return response()->json(["status" => $this->unauthorised, "success" => false, "message" => "Current password does not matched."]);
            }
        } else {
            return response()->json(["status" => $this->unauthorised, "success" => false, "message" => "The password confirmation and password must match."]);
        }
    }
    /**
     *  reset password 
     * 1. send link in the user mail 
     * 2. click email url 
     */
    public function sendPasswordResetEmail(Request $request)
    {
        // If email does not exist
        if (!$this->validEmail($request->email)) {
            return response()->json([
                'message' => 'Email does not exist.'
            ], Response::HTTP_NOT_FOUND);
        } else {
            // If email exists
            $this->sendMail($request->email);
            return response()->json([
                'message' => 'Check your inbox, we have sent a link to reset email.'
            ], Response::HTTP_OK);
        }
    }
    public function sendMail($email)
    {
        $token = $this->generateToken($email);
        Mail::to($email)->send(new SendMail($token));
    }
    public function validEmail($email)
    {
        return !!User::where('email', $email)->first();
    }
    public function generateToken($email)
    {
        $isOtherToken = DB::table('password_resets')->where('email', $email)->first();
        if ($isOtherToken) {
            return $isOtherToken->token;
        }
        $token = Str::random(80);
        $this->storeToken($token, $email);
        return $token;
    }
    public function storeToken($token, $email)
    {
        DB::table('password_resets')->insert([
            'email' => $email,
            'token' => $token,
            'created_at' => Carbon::now()
        ]);
    }

    public function resetPassword(Request $request)
    {
        $user = User::where("email", $request->email)->first();
        if (!is_null($user)) {
            try {
                DB::beginTransaction();
                User::changePassword($user->id, $request->password);
                DB::commit();
                $status = true;
            } catch (\Exception $e) {
                var_dump($e->getMessage());
                DB::rollback();
                $status = false;
            }
            return response()->json(["status" => ($status) ? $this->success : $this->unauthorised, "success" => $status, "message" => ($status) ? "Password Change Successfully!" : "Password Change Failled!"]);
        } else {
            return response()->json(["status" => $this->unauthorised, "success" => false, "message" => "Email doesnt exist."]);
        }
    }
}
