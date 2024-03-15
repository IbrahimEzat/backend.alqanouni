<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatLastMessage;
use App\Models\ChatMessage;
use App\Models\User;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    use GeneralTrait;
    public function addMessage(Request $request)
    {
        DB::beginTransaction();
        try {
            $sender = User::where('id', $request->token['user_id'])->first(['id', 'image', 'name', 'points']);
            if ($sender->points < 1)
                return $this->mainResponse(false, 'لا تملك ما يكفي من النقاط', []);
            $chat = Chat::where('user_id1' , $sender->id)->where('user_id2' , $request->receiverId)->orWhere('user_id2' , $sender->id)->where( 'user_id1' , $request->receiverId)->first();;
            if (!$chat) {
                $chat = Chat::create([
                    'user_id1'=>$sender->id,
                    'user_id2'=>$request->receiverId
                    // 'sender' => $sender->id,
                    // 'receiver' => $request->receiverId
                ]);
            }
            $chatMessage = ChatMessage::create([
                'id' => fake()->uuid(),
                'chat_id' => $chat->id,
                'message' => $request->message,
                'user_send_id' => $sender->id
            ]);
            $chatLastMessage = ChatLastMessage::where('chat_id', $chat->id)->first();
            if (!$chatLastMessage)
                ChatLastMessage::create([
                    'chat_id' => $chat->id,
                    'last_message_id' => $chatMessage->id,
                    'is_read' => false,
                ]);
            else {
                $chatLastMessage->update([
                    'last_message_id' => $chatMessage->id,
                    'is_read' => false,
                ]);
            }
            // $chat->load(['sender:id,name,image']);
            DB::commit();
            $sender->update([
                'points' => --$sender->points
            ]);
            return $this->mainResponse(true, '', ['chat_id'=>$chat->id,'chatMessage_id'=>$chatMessage->id]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->mainResponse(false, '', []);
        }
    }

    public function getUsersConnect(Request $request)
    {
        $chats = Chat::where('user_id1', $request->token['user_id'])->orWhere('user_id2', $request->token['user_id'])
            ->with(['user1:id,name,image','user2:id,name,image', 'lastChatMessage:chat_id,last_message_id,is_read', 'lastChatMessage.message:id,message,user_send_id'])->get();
        return $this->mainResponse(true, 'chats', $chats);
    }

    public function markLastMessageAsRead($chat_id)
    {
        $chatLastMessage = ChatLastMessage::where('chat_id', $chat_id)->first();
        $chatLastMessage->update(['is_read' => true]);
    }

    public function getMessagesInChat(Request $request)
    {

        $chat = Chat::
        where('user_id1' , $request->token['user_id'])->where('user_id2' , $request->otherSide)->orWhere('user_id2' , $request->token['user_id'])->where( 'user_id1' , $request->otherSide)->first(['id']);
        $messages = [];
        if($chat)
            $messages = ChatMessage::where('chat_id', $chat->id)->orderBy('created_at','desc')->get();
        $otherSide = User::where('id', $request->otherSide)->first(['name', 'image', 'id']);
        return $this->mainResponse(true, 'chats', ['messages' => $messages, 'otherSide' => $otherSide]);
    }
}
