import {Routes, Route} from 'react-router-dom';
import Home from './pages/Home.jsx';
import Register from './pages/Register.jsx';
import Profile from './pages/Profile.jsx';
import Start from './pages/Start.jsx';
import Control from './pages/Control.jsx';
import Support from './pages/Support.jsx';
import { useState } from 'react'
import './App.css'

function App() {
  return (
	<Routes>
		<Route path="/" element={<Home />} />
		<Route path="/register" element={<Register />} />
		<Route path="/profile" element={<Profile />} />
		<Route path="/start" element={<Start />} />
		<Route path="/control" element={<Control />} />
		<Route path="/support" element={<Support />} />
	</Routes>
  )
}

export default App
