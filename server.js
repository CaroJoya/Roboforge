const express = require('express');
const http = require('http');
const socketIo = require('socket.io');
const cors = require('cors');

const app = express();
const server = http.createServer(app);
const io = socketIo(server, {
    cors: { origin: "http://localhost", methods: ["GET", "POST"] }
});

app.use(cors());
app.use(express.json());

io.on('connection', (socket) => {
    console.log('User connected:', socket.id);
    
    // Join a specific chat room
    socket.on('joinRoom', (data) => {
        socket.join(data.chatId);
        console.log(`User joined room: ${data.chatId}`);
    });
    
    // Handle sending messages
    socket.on('sendMessage', (data) => {
        io.to(data.chatId).emit('message', data);
    });
    
    // Handle typing indicator
    socket.on('typing', (data) => {
        socket.to(data.chatId).emit('userTyping', {
            username: data.username
        });
    });
    
    socket.on('stopTyping', (data) => {
        socket.to(data.chatId).emit('userStopTyping');
    });
    
    socket.on('disconnect', () => {
        console.log('User disconnected:', socket.id);
    });
});

server.listen(3000, () => {
    console.log('Real-time server running on http://localhost:3000');
});