import React from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { Container } from 'react-bootstrap';
import FeedList from './components/FeedList';
import AddFeed from './components/AddFeed';
import PostsPreview from './components/PostsPreview';
import Navbar from './components/Navbar';

function App() {
  return (
    <Router>
      <div className="App">
        <Navbar />
        <Container className="mt-4">
          <Routes>
            <Route path="/" element={<PostsPreview />} />
            <Route path="/feeds" element={<FeedList />} />
            <Route path="/feeds/add" element={<AddFeed />} />
          </Routes>
        </Container>
      </div>
    </Router>
  );
}

export default App; 