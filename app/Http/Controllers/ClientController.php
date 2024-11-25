<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Client;
use App\Models\City;

class ClientController extends Controller
{
    public function ClientLogin(){
        return view('client.client_login');
   }

   public function ClientRegister(){
    return view('client.client_register');
}

    public function ClientRegisterSubmit(Request $request) {
        $request->validate([
            'name' => ['required','string','max:200'],
            'email' => ['required', 'string','unique:clients']
        ]);

        Client::insert([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'password' => Hash::make($request->password),
            'role' => 'client',
            'status' => '0',
        ]);

        $notification = array(
            'message' => 'Client Register Updated Successfully',
            'alert-type' => 'success'
        );
        return redirect()->route('client.login')->with($notification);
    }

    public function ClientLoginSubmit(Request $request){
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        $check = $request->all();
        $data = [
            'email' => $check['email'],
            'password' => $check['password'],
        ];
        if (Auth::guard('client')->attempt($data)) {
            return redirect()->route('client.dashboard')->with('success','Login Successfully');
        }else{
            return redirect()->route('client.login')->with('error','Invalid Creadentials');
        }

    }

    public function ClientDashboard () {
        return view ('client.index');
    }

    public function ClientLogout()
    {
        Auth::guard('client')->logout();
        return redirect()->route('client.login')->with('success', 'Logout Success');
    }
    // End Method 

    public function ClientProfile(){
        $city = City::latest()->get();
        $id = Auth::guard('client')->id();
        $profileData = Client::find($id);
        return view('client.client_profile',compact('profileData','city'));
     }

     public function ClientProfileStore(Request $request) {
        $id = Auth::guard('client')->id();
        $data = Client::find($id);

        $data->name = $request->name;
        $data->email = $request->email;
        $data->phone = $request->phone;
        $data->address = $request->address;
        $data->city_id = $request->city_id;
        $data->shop_info = $request->shop_info;
        $oldPhotoPath = $data->photo;

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = time().'.'.$file->getClientOriginalExtension();

            // Pindahkan file ke folder yang sesuai
            $file->move(public_path('upload/client_images'), $filename);
            $data->photo = $filename;

            // Hapus foto lama jika ada dan berbeda dengan yang baru
            if ($oldPhotoPath && $oldPhotoPath !== $filename) {
                $this->deleteOldImage($oldPhotoPath);
            }
        }

        if ($request->hasFile('cover_photo')) {
            $file1 = $request->file('cover_photo');
            $filename1 = time().'.'.$file1->getClientOriginalExtension();
            $file1->move(public_path('upload/client_images'),$filename1);
            $data->cover_photo = $filename1;
         }

        $data->save();

        $notification = array(
            'message' => 'Profile Updated Successfully',
            'alert-type' => 'success'
        );
        return redirect()->back()->with($notification);
    }

    private function deleteOldImage(string $oldPhotoPath): void
    {
        $fullPath = public_path('upload/client_images/' . $oldPhotoPath);
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
    // End Private Method 

    public function ClientChangePassword()
    {
        $id = Auth::guard('client')->id();
        $profileData = Client::find($id);
        return view('client.client_change_Password', compact('profileData'));
    }
    // End Method 

    public function ClientPasswordUpdate(Request $request)
    {
        $client = Auth::guard('client')->user();
        $request->validate([
            'old_password' =>'required',
            'new_password' =>'required|confirmed'
        ]);
    
        if (!Hash::check($request->old_password, $client->password)){
            $notification = array(
                'message' => 'Old Password Does Not Match!',
                'alert-type' => 'error'
            );
            return back()->with($notification);
        }
        ///update the new password

        Client::whereId($client->id)->update([
            'password' => Hash::make($request->new_password)
        ]);

        $notification = array(
            'message' => 'Password Change Successfully',
            'alert-type' => 'success'
        );
        return back()->with($notification);
    }
     // End Method
}