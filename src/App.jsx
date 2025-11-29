import {Routes, Route} from 'react-router-dom';
import Home from './pages/Home.jsx';
import Register from './pages/Register.jsx';
import { useState } from 'react'
import './App.css'

function App() {
  return (
	<Routes>
		<Route path="/" element={<Home />} />
		<Route path="/register" element={<Register />} />
	</Routes>
  )
}

export default App
