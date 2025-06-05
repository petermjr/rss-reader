import React, { useState, useEffect } from 'react';
import { Card, Button, Row, Col } from 'react-bootstrap';
import { Link } from 'react-router-dom';
import axios from 'axios';

function FeedList() {
  const [feeds, setFeeds] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchFeeds();
  }, []);

  const fetchFeeds = async () => {
    try {
      const response = await axios.get('/api/feeds');
      setFeeds(response.data);
      setLoading(false);
    } catch (err) {
      setError('Failed to fetch feeds');
      setLoading(false);
    }
  };

  const handleDelete = async (id) => {
    try {
      await axios.delete(`/api/feeds/${id}`);
      setFeeds(feeds.filter(feed => feed.id !== id));
    } catch (err) {
      setError('Failed to delete feed');
    }
  };

  if (loading) return <div>Loading...</div>;
  if (error) return <div className="text-danger">{error}</div>;

  return (
    <div>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <h2>Your RSS Feeds</h2>
        <Button as={Link} to="/add" variant="primary">
          Add New Feed
        </Button>
      </div>
      <Row>
        {feeds.map(feed => (
          <Col key={feed.id} md={4} className="mb-4">
            <Card>
              <Card.Body>
                <Card.Title>{feed.title}</Card.Title>
                <Card.Text>{feed.description}</Card.Text>
                <div className="d-flex justify-content-between">
                  <Button
                    as={Link}
                    to={`/feed/${feed.id}`}
                    variant="info"
                    size="sm"
                  >
                    View
                  </Button>
                  <Button
                    variant="danger"
                    size="sm"
                    onClick={() => handleDelete(feed.id)}
                  >
                    Delete
                  </Button>
                </div>
              </Card.Body>
            </Card>
          </Col>
        ))}
      </Row>
    </div>
  );
}

export default FeedList; 